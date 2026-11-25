<?php

declare(strict_types=1);

namespace MauticPlugin\MauticPasskeyBundle\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\UserBundle\Event\UserEvent;
use Mautic\UserBundle\UserEvents;
use MauticPlugin\MauticPasskeyBundle\Entity\PasskeyCredential;
use MauticPlugin\MauticPasskeyBundle\Entity\PasskeyCredentialRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class UserDeleteSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [UserEvents::USER_POST_DELETE => ['onUserDelete', 0]];
    }

    public function onUserDelete(UserEvent $event): void
    {
        $userId = (int) $event->getUser()->getId();
        $repository = $this->entityManager->getRepository(PasskeyCredential::class);
        if ($userId > 0 && $repository instanceof PasskeyCredentialRepository) {
            $repository->deleteByUserId($userId);
        }
    }
}

