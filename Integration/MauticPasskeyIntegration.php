<?php

declare(strict_types=1);

namespace MauticPlugin\MauticPasskeyBundle\Integration;

use Mautic\PluginBundle\Integration\AbstractIntegration;

final class MauticPasskeyIntegration extends AbstractIntegration
{
    public const NAME = 'MauticPasskey';

    public function getName(): string
    {
        return self::NAME;
    }

    public function getDisplayName(): string
    {
        return 'Passkey Login';
    }

    public function getDescription(): string
    {
        return 'Passwordless sign-in with a device passkey.';
    }

    public function getAuthenticationType(): string
    {
        return 'none';
    }

    public function getIcon(): string
    {
        return 'plugins/MauticPasskeyBundle/Assets/img/passkey.png';
    }

    public function getPriority(): int
    {
        return 20;
    }

    public function getSupportedFeatures(): array
    {
        return [];
    }

    public function getRequiredKeyFields(): array
    {
        return [];
    }

}

