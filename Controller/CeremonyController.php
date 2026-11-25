<?php

declare(strict_types=1);

namespace MauticPlugin\MauticPasskeyBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Security\Provider\UserProvider;
use MauticPlugin\MauticPasskeyBundle\Entity\PasskeyCredential;
use MauticPlugin\MauticPasskeyBundle\Entity\PasskeyCredentialRepository;
use MauticPlugin\MauticPasskeyBundle\Integration\MauticPasskeyIntegration;
use MauticPlugin\MauticPasskeyBundle\Service\PasskeyCeremonyService;
use MauticPlugin\MauticPasskeyBundle\Service\PasskeyChallengeStore;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class CeremonyController extends AbstractController
{
    public function assertionOptionsAction(
        Request $request,
        IntegrationHelper $integrationHelper,
        CsrfTokenManagerInterface $csrfTokenManager,
        PasskeyCeremonyService $ceremonyService,
        PasskeyChallengeStore $challengeStore,
        TranslatorInterface $translator,
        LoggerInterface $logger,
    ): JsonResponse {
        if (!$this->isReady($integrationHelper)) {
            return $this->error($translator->trans('mautic.passkey.error.unavailable'), 404);
        }

        $payload = $this->payload($request);
        if (!$this->validLoginCsrf($payload, $csrfTokenManager)) {
            return $this->error($translator->trans('mautic.passkey.error.invalid_request'), 403);
        }

        try {
            $result = $ceremonyService->createAssertionOptions($request);
            $challengeStore->put($request, PasskeyChallengeStore::ASSERTION, [
                'origin'       => $result['origin'],
                'rp_id'        => $result['rp_id'],
                'options_json' => $result['json'],
            ]);

            return $this->jsonOptions($result['json']);
        } catch (\Throwable $exception) {
            $logger->warning('Passkey assertion options failed.', ['exception' => $exception]);

            return $this->error($translator->trans('mautic.passkey.error.unavailable'), 400);
        }
    }

    public function registrationOptionsAction(
        Request $request,
        IntegrationHelper $integrationHelper,
        CsrfTokenManagerInterface $csrfTokenManager,
        UserProvider $userProvider,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
        PasskeyCeremonyService $ceremonyService,
        PasskeyChallengeStore $challengeStore,
        TranslatorInterface $translator,
        LoggerInterface $logger,
    ): JsonResponse {
        if (!$this->isReady($integrationHelper)) {
            return $this->error($translator->trans('mautic.passkey.error.unavailable'), 404);
        }

        $payload = $this->payload($request);
        if (!$this->validLoginCsrf($payload, $csrfTokenManager)) {
            return $this->error($translator->trans('mautic.passkey.error.invalid_request'), 403);
        }

        $identifier = trim((string) ($payload['username'] ?? ''));
        $password   = (string) ($payload['password'] ?? '');
        if ('' === $identifier || '' === $password) {
            return $this->error($translator->trans('mautic.passkey.error.credentials_required'), 422);
        }

        try {
            $user = $userProvider->loadUserByIdentifier($identifier);
        } catch (UserNotFoundException) {
            $dummy = (new User())->setPassword('$2y$13$aAwXNyqA87lcXQQuk8Cp6eo2amRywLct29oG2uWZ8lYBeamFZ8UhK');
            $passwordHasher->isPasswordValid($dummy, $password);

            return $this->error($translator->trans('mautic.user.auth.error.invalidlogin'), 401);
        }

        if (!$passwordHasher->isPasswordValid($user, $password)) {
            return $this->error($translator->trans('mautic.user.auth.error.invalidlogin'), 401);
        }

        try {
            $repository = $entityManager->getRepository(PasskeyCredential::class);
            if (!$repository instanceof PasskeyCredentialRepository) {
                throw new \RuntimeException('Passkey repository is unavailable.');
            }

            $result = $ceremonyService->createRegistrationOptions($request, $user, $repository);
            $challengeStore->put($request, PasskeyChallengeStore::REGISTRATION, [
                'origin'       => $result['origin'],
                'rp_id'        => $result['rp_id'],
                'options_json' => $result['json'],
                'user_id'      => (int) $user->getId(),
                'user_handle'  => $result['user_handle'],
            ]);

            return $this->jsonOptions($result['json']);
        } catch (\Throwable $exception) {
            $logger->warning('Passkey registration options failed.', [
                'user_id'   => (int) $user->getId(),
                'exception' => $exception,
            ]);

            return $this->error($translator->trans('mautic.passkey.error.unavailable'), 400);
        }
    }

    /** @return array<string, mixed> */
    private function payload(Request $request): array
    {
        try {
            $payload = json_decode($request->getContent(), true, 32, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return [];
        }

        return is_array($payload) ? $payload : [];
    }

    /** @param array<string, mixed> $payload */
    private function validLoginCsrf(array $payload, CsrfTokenManagerInterface $csrfTokenManager): bool
    {
        return $csrfTokenManager->isTokenValid(new CsrfToken(
            'authenticate',
            (string) ($payload['_csrf_token'] ?? '')
        ));
    }

    private function isReady(IntegrationHelper $integrationHelper): bool
    {
        $integration = $integrationHelper->getIntegrationObject(MauticPasskeyIntegration::NAME);

        return $integration instanceof MauticPasskeyIntegration
            && $integration->getIntegrationSettings()->isPublished();
    }

    private function jsonOptions(string $json): JsonResponse
    {
        $response = new JsonResponse($json, 200, [], true);
        $response->headers->set('Cache-Control', 'no-store, private');

        return $response;
    }

    private function error(string $message, int $status): JsonResponse
    {
        $response = new JsonResponse(['error' => $message], $status);
        $response->headers->set('Cache-Control', 'no-store, private');

        return $response;
    }
}

