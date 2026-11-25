<?php

declare(strict_types=1);

namespace MauticPlugin\MauticPasskeyBundle\Service;

use Cose\Algorithm\Manager;
use Cose\Algorithm\Signature\ECDSA\ES256;
use Cose\Algorithm\Signature\RSA\RS256;
use Mautic\UserBundle\Entity\User;
use MauticPlugin\MauticPasskeyBundle\Entity\PasskeyCredential;
use MauticPlugin\MauticPasskeyBundle\Entity\PasskeyCredentialRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;
use Webauthn\AttestationStatement\NoneAttestationStatementSupport;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\AuthenticatorSelectionCriteria;
use Webauthn\CeremonyStep\CeremonyStepManagerFactory;
use Webauthn\CredentialRecord;
use Webauthn\Denormalizer\WebauthnSerializerFactory;
use Webauthn\PublicKeyCredential;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\PublicKeyCredentialParameters;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialUserEntity;

final class PasskeyCeremonyService
{
    private const TIMEOUT_MS = 60000;

    private Serializer $serializer;

    private AttestationStatementSupportManager $attestationManager;

    private Manager $algorithmManager;

    public function __construct()
    {
        $this->attestationManager = new AttestationStatementSupportManager();
        $this->attestationManager->add(new NoneAttestationStatementSupport());
        $this->algorithmManager = Manager::create()->add(ES256::create(), RS256::create());

        $serializer = (new WebauthnSerializerFactory($this->attestationManager))->create();
        if (!$serializer instanceof Serializer) {
            throw new \RuntimeException('Unable to initialize the WebAuthn serializer.');
        }
        $this->serializer = $serializer;
    }

    /** @return array{options: PublicKeyCredentialRequestOptions, json: string, rp_id: string, origin: string} */
    public function createAssertionOptions(Request $request): array
    {
        [$rpId, $origin] = $this->requestContext($request);
        $options = PublicKeyCredentialRequestOptions::create(
            random_bytes(32),
            $rpId,
            [],
            PublicKeyCredentialRequestOptions::USER_VERIFICATION_REQUIREMENT_REQUIRED,
            self::TIMEOUT_MS,
        );

        return [
            'options' => $options,
            'json'    => $this->serializeOptions($options),
            'rp_id'   => $rpId,
            'origin'  => $origin,
        ];
    }

    /** @return array{options: PublicKeyCredentialCreationOptions, json: string, rp_id: string, origin: string, user_handle: string} */
    public function createRegistrationOptions(
        Request $request,
        User $user,
        PasskeyCredentialRepository $repository,
    ): array {
        [$rpId, $origin] = $this->requestContext($request);
        $existing       = $repository->findFirstByUserId((int) $user->getId());
        $userHandle     = $existing instanceof PasskeyCredential
            ? $this->base64UrlDecode($existing->getUserHandle())
            : random_bytes(32);

        $excludeCredentials = [];
        foreach ($repository->findByUserId((int) $user->getId()) as $credential) {
            $excludeCredentials[] = PublicKeyCredentialDescriptor::create(
                'public-key',
                $this->base64UrlDecode($credential->getCredentialId())
            );
        }

        $username    = trim((string) $user->getUserIdentifier());
        $displayName = trim((string) $user->getName(true));
        $options     = PublicKeyCredentialCreationOptions::create(
            PublicKeyCredentialRpEntity::create('Sales Snap Mautic', $rpId),
            PublicKeyCredentialUserEntity::create($username, $userHandle, $displayName ?: $username),
            random_bytes(32),
            [
                PublicKeyCredentialParameters::create('public-key', -7),
                PublicKeyCredentialParameters::create('public-key', -257),
            ],
            AuthenticatorSelectionCriteria::create(
                null,
                AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_REQUIRED,
                AuthenticatorSelectionCriteria::RESIDENT_KEY_REQUIREMENT_REQUIRED,
            ),
            PublicKeyCredentialCreationOptions::ATTESTATION_CONVEYANCE_PREFERENCE_NONE,
            $excludeCredentials,
            self::TIMEOUT_MS,
        );

        return [
            'options'     => $options,
            'json'        => $this->serializeOptions($options),
            'rp_id'       => $rpId,
            'origin'      => $origin,
            'user_handle' => $this->base64UrlEncode($userHandle),
        ];
    }

    public function decodeCredential(string $json): PublicKeyCredential
    {
        $credential = $this->serializer->deserialize($json, PublicKeyCredential::class, 'json');
        if (!$credential instanceof PublicKeyCredential) {
            throw new \RuntimeException('Invalid passkey response.');
        }

        return $credential;
    }

    public function verifyRegistration(
        Request $request,
        PublicKeyCredential $credential,
        string $optionsJson,
    ): CredentialRecord {
        if (!$credential->response instanceof AuthenticatorAttestationResponse) {
            throw new \RuntimeException('Invalid passkey registration response.');
        }

        $options = $this->serializer->deserialize(
            $optionsJson,
            PublicKeyCredentialCreationOptions::class,
            'json'
        );
        if (!$options instanceof PublicKeyCredentialCreationOptions) {
            throw new \RuntimeException('Invalid passkey registration options.');
        }

        return AuthenticatorAttestationResponseValidator::create(
            $this->ceremonyFactory($request)->creationCeremony()
        )->check($credential->response, $options, $request->getHost());
    }

    public function verifyAssertion(
        Request $request,
        PasskeyCredential $storedCredential,
        PublicKeyCredential $credential,
        string $optionsJson,
    ): CredentialRecord {
        if (!$credential->response instanceof AuthenticatorAssertionResponse) {
            throw new \RuntimeException('Invalid passkey assertion response.');
        }

        $storedUserHandle = $this->base64UrlDecode($storedCredential->getUserHandle());
        if (null === $credential->response->userHandle
            || '' === $credential->response->userHandle
            || !hash_equals($storedUserHandle, $credential->response->userHandle)
        ) {
            throw new \RuntimeException('The passkey is not bound to this user.');
        }

        $options = $this->serializer->deserialize(
            $optionsJson,
            PublicKeyCredentialRequestOptions::class,
            'json'
        );
        if (!$options instanceof PublicKeyCredentialRequestOptions) {
            throw new \RuntimeException('Invalid passkey assertion options.');
        }

        return AuthenticatorAssertionResponseValidator::create(
            $this->ceremonyFactory($request)->requestCeremony()
        )->check(
            $this->deserializeRecord($storedCredential->getCredentialRecord()),
            $credential->response,
            $options,
            $request->getHost(),
            $storedUserHandle,
        );
    }

    public function serializeRecord(CredentialRecord $record): string
    {
        return $this->serializer->serialize($record, 'json');
    }

    public function deserializeRecord(string $json): CredentialRecord
    {
        $record = $this->serializer->deserialize($json, CredentialRecord::class, 'json');
        if (!$record instanceof CredentialRecord) {
            throw new \RuntimeException('Invalid stored passkey.');
        }

        return $record;
    }

    public function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    public function base64UrlDecode(string $value): string
    {
        $padding = (4 - strlen($value) % 4) % 4;
        $decoded = base64_decode(strtr($value, '-_', '+/').str_repeat('=', $padding), true);
        if (false === $decoded || '' === $decoded) {
            throw new \RuntimeException('Invalid passkey binary value.');
        }

        return $decoded;
    }

    private function serializeOptions(object $options): string
    {
        return $this->serializer->serialize($options, 'json', [
            'json_encode_options' => JSON_THROW_ON_ERROR,
            AbstractObjectNormalizer::SKIP_NULL_VALUES => true,
        ]);
    }

    private function ceremonyFactory(Request $request): CeremonyStepManagerFactory
    {
        [, $origin] = $this->requestContext($request);
        $factory = new CeremonyStepManagerFactory();
        $factory->setAttestationStatementSupportManager($this->attestationManager);
        $factory->setAlgorithmManager($this->algorithmManager);
        $factory->setAllowedOrigins([$origin]);

        return $factory;
    }

    /** @return array{0: string, 1: string} */
    private function requestContext(Request $request): array
    {
        $rpId   = strtolower($request->getHost());
        $origin = $request->getSchemeAndHttpHost();
        $local  = in_array($rpId, ['localhost', '127.0.0.1', '::1'], true);

        if ('' === $rpId || '' === $origin || (!$request->isSecure() && !$local)) {
            throw new \RuntimeException('Passkeys require HTTPS.');
        }

        return [$rpId, $origin];
    }
}

