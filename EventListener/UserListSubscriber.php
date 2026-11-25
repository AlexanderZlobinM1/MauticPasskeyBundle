<?php

declare(strict_types=1);

namespace MauticPlugin\MauticPasskeyBundle\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\CustomTemplateEvent;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use MauticPlugin\MauticPasskeyBundle\Entity\PasskeyCredential;
use MauticPlugin\MauticPasskeyBundle\Entity\PasskeyCredentialRepository;
use MauticPlugin\MauticPasskeyBundle\Integration\MauticPasskeyIntegration;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class UserListSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly IntegrationHelper $integrationHelper,
        private readonly CorePermissions $permissions,
        private readonly UserHelper $userHelper,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [CoreEvents::VIEW_INJECT_CUSTOM_TEMPLATE => ['onTemplateRender', 100]];
    }

    public function onTemplateRender(CustomTemplateEvent $event): void
    {
        if ('@MauticUser/User/list.html.twig' !== $event->getTemplate()) {
            return;
        }

        $integration = $this->integrationHelper->getIntegrationObject(MauticPasskeyIntegration::NAME);
        if (!$integration instanceof MauticPasskeyIntegration
            || !$integration->getIntegrationSettings()->isPublished()
        ) {
            return;
        }

        $vars        = $event->getVars();
        $items       = $vars['items'] ?? [];
        $userIds     = [];
        $currentUser = $this->userHelper->getUser();
        $currentId   = $currentUser ? (int) $currentUser->getId() : 0;

        foreach ($items as $user) {
            if (null !== $user->getId()) {
                $userIds[] = (int) $user->getId();
            }
        }

        try {
            $repository = $this->entityManager->getRepository(PasskeyCredential::class);
            $counts = $repository instanceof PasskeyCredentialRepository
                ? $repository->countByUserIds($userIds)
                : [];
        } catch (\Throwable) {
            $counts = [];
        }
        $rows = [];

        foreach ($items as $user) {
            $userId = (int) $user->getId();
            $rows[$userId] = [
                'count'      => (int) ($counts[$userId] ?? 0),
                'registered' => (int) ($counts[$userId] ?? 0) > 0,
                'can_manage' => $userId === $currentId
                    ? (bool) $this->permissions->isGranted('user:users:editown')
                    : (bool) $this->permissions->isGranted('user:users:editother'),
            ];
        }

        $vars['mautic_passkey_user_rows'] = $rows;
        $event->setVars($vars);

        $authGuard = $this->integrationHelper->getIntegrationObject('AuthGuard');
        if ($authGuard && $authGuard->getIntegrationSettings()->isPublished()) {
            // AuthGuard's composable Users template consumes the rows at its
            // lower event priority and renders both plugins' columns.
            return;
        }

        $event->setTemplate('@MauticPasskey/User/list.html.twig');
    }
}

