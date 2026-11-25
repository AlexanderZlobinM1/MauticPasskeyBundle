<?php

declare(strict_types=1);

namespace MauticPlugin\MauticPasskeyBundle\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Event\AuthenticationEvent;
use Mautic\UserBundle\UserEvents;
use MauticPlugin\MauticPasskeyBundle\Entity\PasskeyCredential;
use MauticPlugin\MauticPasskeyBundle\Entity\PasskeyCredentialRepository;
use MauticPlugin\MauticPasskeyBundle\Integration\MauticPasskeyIntegration;
use MauticPlugin\MauticPasskeyBundle\Service\PasskeyCeremonyService;
use MauticPlugin\MauticPasskeyBundle\Service\PasskeyChallengeStore;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class PasskeyAuthenticationSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly IntegrationHelper $integrationHelper,
        private readonly RouterInterface $router,
        private readonly TranslatorInterface $translator,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
        private readonly PasskeyCeremonyService $ceremonyService,
        private readonly PasskeyChallengeStore $challengeStore,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [UserEvents::USER_PRE_AUTHENTICATION => ['onUserPreAuthentication', 0]];
    }

    public function onUserPreAuthentication(AuthenticationEvent $event): void
    {
        if (MauticPasskeyIntegration::NAME !== (string) $event->getAuthenticatingService()) {
            return;
        }

        if (!$this->isReady()) {
            $this->fail($event, 'mautic.passkey.error.unavailable');

            return;
        }

        if (!$event->isLoginCheck()) {
            $event->setResponse(new RedirectResponse($this->router->generate('login')));

            return;
        }

        $request = $event->getRequest();
        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken(
            'authenticate',
            (string) $request->request->get('_csrf_token', '')
        ))) {
            $this->fail($event, 'mautic.passkey.error.invalid_request');

            return;
        }

        $kind = (string) $request->request->get('passkey_ceremony', '');
        $json = (string) $request->request->get('passkey_credential', '');
        if (!in_array($kind, [PasskeyChallengeStore::ASSERTION, PasskeyChallengeStore::REGISTRATION], true)
            || '' === $json
            || strlen($json) > 131072
        ) {
            $this->fail($event, 'mautic.passkey.error.invalid_request');

            return;
        }

        try {
            $context    = $this->challengeStore->consume($request, $kind);
            $credential = $this->ceremonyService->decodeCredential($json);
            $repository = $this->entityManager->getRepository(PasskeyCredential::class);
            if (!$repository instanceof PasskeyCredentialRepository) {
                throw new \RuntimeException('Passkey repository is unavailable.');
            }

            if (PasskeyChallengeStore::ASSERTION === $kind) {
                $stored = $repository->findByRawCredentialId($credential->rawId);
                if (!$stored instanceof PasskeyCredential) {
                    throw new \RuntimeException('Unknown passkey credential.');
                }

                $record = $this->ceremonyService->verifyAssertion(
                    $request,
                    $stored,
                    $credential,
                    (string) ($context['options_json'] ?? '')
                );
                $stored->setCredentialRecord($this->ceremonyService->serializeRecord($record));
                $stored->setLastUsedAt(new \DateTimeImmutable('now', new \DateTimeZone('UTC')));
                $user = $this->loadPublishedUser($event, $stored->getUserId());
            } else {
                $userId = (int) ($context['user_id'] ?? 0);
                $user   = $this->loadPublishedUser($event, $userId);
                $record = $this->ceremonyService->verifyRegistration(
                    $request,
                    $credential,
                    (string) ($context['options_json'] ?? '')
                );

                $expectedHandle = $this->ceremonyService->base64UrlDecode((string) ($context['user_handle'] ?? ''));
                if (!hash_equals($expectedHandle, $record->userHandle)
                    || $repository->findByRawCredentialId($record->publicKeyCredentialId)
                ) {
                    throw new \RuntimeException('Passkey credential binding is invalid.');
                }

                $stored = (new PasskeyCredential())
                    ->setUserId($userId)
                    ->setCredentialIdHash(hash('sha256', $record->publicKeyCredentialId))
                    ->setCredentialId($this->ceremonyService->base64UrlEncode($record->publicKeyCredentialId))
                    ->setUserHandle($this->ceremonyService->base64UrlEncode($record->userHandle))
                    ->setCredentialRecord($this->ceremonyService->serializeRecord($record))
                    ->setCreatedAt(new \DateTimeImmutable('now', new \DateTimeZone('UTC')));
                $this->entityManager->persist($stored);
            }

            $this->entityManager->flush();
            $event->setIsAuthenticated(MauticPasskeyIntegration::NAME, $user, false);
        } catch (\Throwable $exception) {
            $this->logger->warning('Passkey authentication failed.', [
                'ceremony'  => $kind,
                'exception' => $exception,
            ]);
            $this->fail($event, 'mautic.passkey.error.authentication_failed');
        }
    }

    private function loadPublishedUser(AuthenticationEvent $event, int $userId): User
    {
        $user = $this->entityManager->getRepository(User::class)->find($userId);
        if (!$user instanceof User) {
            throw new \RuntimeException('Mautic user not found.');
        }

        $provider = $event->getUserProvider();
        $loaded   = method_exists($provider, 'loadUserByIdentifier')
            ? $provider->loadUserByIdentifier($user->getUserIdentifier())
            : $provider->loadUserByUsername($user->getUserIdentifier());

        if (!$loaded instanceof User || (int) $loaded->getId() !== $userId) {
            throw new \RuntimeException('Mautic user is unavailable.');
        }

        return $loaded;
    }

    private function isReady(): bool
    {
        $integration = $this->integrationHelper->getIntegrationObject(MauticPasskeyIntegration::NAME);

        return $integration instanceof MauticPasskeyIntegration
            && $integration->getIntegrationSettings()->isPublished();
    }

    private function fail(AuthenticationEvent $event, string $translationKey): void
    {
        $message = $this->translator->trans($translationKey);
        $event->setFailedAuthenticationMessage($message);
        if ($event->getRequest()->hasSession()) {
            $event->getRequest()->getSession()->getFlashBag()->add('error', $message);
        }
        $event->setResponse(new RedirectResponse($this->router->generate('login')));
    }
}

