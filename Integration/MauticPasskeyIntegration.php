<?php

declare(strict_types=1);

namespace MauticPlugin\MauticPasskeyBundle\Integration;

use Mautic\CoreBundle\Form\Type\YesNoButtonGroupType;
use Mautic\PluginBundle\Integration\AbstractIntegration;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilder;

final class MauticPasskeyIntegration extends AbstractIntegration
{
    public const NAME = 'MauticPasskey';

    private const SHOW_LOGIN_BUTTON_FIELD = 'show_login_button';

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
        return 'plugins/MauticPasskeyBundle/Assets/img/passkey.svg';
    }

    public function getPriority(): int
    {
        return 20;
    }

    public function getSupportedFeatures(): array
    {
        return ['sso_service'];
    }

    public function getRequiredKeyFields(): array
    {
        return [];
    }

    /** @param FormBuilder|Form $builder */
    public function appendToForm(&$builder, $data, $formArea): void
    {
        if ('keys' !== $formArea) {
            return;
        }

        $builder->add(self::SHOW_LOGIN_BUTTON_FIELD, YesNoButtonGroupType::class, [
            'label' => 'mautic.passkey.show_login_button',
            'data'  => array_key_exists(self::SHOW_LOGIN_BUTTON_FIELD, $data)
                ? (bool) $data[self::SHOW_LOGIN_BUTTON_FIELD]
                : true,
            'attr'  => ['tooltip' => 'mautic.passkey.show_login_button.tooltip'],
        ]);
    }

    public function shouldShowLoginButton(): bool
    {
        return !array_key_exists(self::SHOW_LOGIN_BUTTON_FIELD, $this->keys)
            || (bool) $this->keys[self::SHOW_LOGIN_BUTTON_FIELD];
    }

    public function getFormNotes($section)
    {
        if ('custom' === $section) {
            return [
                'custom'     => true,
                'template'   => '@MauticPasskey/Integration/form.html.twig',
                'parameters' => [],
            ];
        }

        return parent::getFormNotes($section);
    }
}

