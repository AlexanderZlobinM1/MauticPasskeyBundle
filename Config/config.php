<?php

declare(strict_types=1);

return [
    'name'        => 'Passkey Login',
    'description' => 'Passwordless passkey sign-in for Mautic.',
    'version'     => '1.0.4',
    'author'      => 'Sales Snap',
    'routes'      => [
        'main' => [
            'mautic_passkey_user_clear' => [
                'path'       => '/passkey/user/clear',
                'controller' => '\\MauticPlugin\\MauticPasskeyBundle\\Controller\\UserPasskeyController::clearAction',
                'method'     => 'POST',
            ],
        ],
        'public' => [
            'mautic_passkey_assertion_options' => [
                'path'       => '/passkey/assertion/options',
                'controller' => '\\MauticPlugin\\MauticPasskeyBundle\\Controller\\CeremonyController::assertionOptionsAction',
                'method'     => 'POST',
            ],
            'mautic_passkey_registration_options' => [
                'path'       => '/passkey/registration/options',
                'controller' => '\\MauticPlugin\\MauticPasskeyBundle\\Controller\\CeremonyController::registrationOptionsAction',
                'method'     => 'POST',
            ],
        ],
    ],
    'services' => [
        'events' => [
            'plugin.mautic_passkey.authentication_subscriber' => [
                'class'     => MauticPlugin\MauticPasskeyBundle\EventListener\PasskeyAuthenticationSubscriber::class,
                'arguments' => [
                    'mautic.helper.integration',
                    'router',
                    'translator',
                    'doctrine.orm.entity_manager',
                    'logger',
                    'security.csrf.token_manager',
                    'plugin.mautic_passkey.ceremony',
                    'plugin.mautic_passkey.challenge_store',
                ],
            ],
            'plugin.mautic_passkey.login_button_subscriber' => [
                'class'     => MauticPlugin\MauticPasskeyBundle\EventListener\LoginButtonSubscriber::class,
                'arguments' => [
                    'mautic.helper.integration',
                    'router',
                    'translator',
                    'doctrine.orm.entity_manager',
                ],
            ],
            'plugin.mautic_passkey.user_list_subscriber' => [
                'class'     => MauticPlugin\MauticPasskeyBundle\EventListener\UserListSubscriber::class,
                'arguments' => [
                    'mautic.helper.integration',
                    'mautic.security',
                    'mautic.helper.user',
                    'doctrine.orm.entity_manager',
                ],
            ],
            'plugin.mautic_passkey.user_delete_subscriber' => [
                'class'     => MauticPlugin\MauticPasskeyBundle\EventListener\UserDeleteSubscriber::class,
                'arguments' => ['doctrine.orm.entity_manager'],
            ],
        ],
        'integrations' => [
            // IntegrationHelper derives this service ID from the integration
            // class name (MauticPasskeyIntegration -> mauticpasskey).
            'mautic.integration.mauticpasskey' => [
                'class'     => MauticPlugin\MauticPasskeyBundle\Integration\MauticPasskeyIntegration::class,
                'arguments' => [
                    'event_dispatcher',
                    'mautic.helper.cache_storage',
                    'doctrine.orm.entity_manager',
                    'request_stack',
                    'router',
                    'translator',
                    'logger',
                    'mauticpasskey.helper.encryption',
                    'mautic.lead.model.lead',
                    'mautic.lead.model.company',
                    'mautic.helper.paths',
                    'mautic.core.model.notification',
                    'mautic.lead.model.field',
                    'mautic.plugin.model.integration_entity',
                    'mautic.lead.model.dnc',
                    'mautic.lead.field.fields_with_unique_identifier',
                ],
            ],
        ],
        'other' => [
            'plugin.mautic_passkey.ceremony' => [
                'class' => MauticPlugin\MauticPasskeyBundle\Service\PasskeyCeremonyService::class,
            ],
            'plugin.mautic_passkey.challenge_store' => [
                'class' => MauticPlugin\MauticPasskeyBundle\Service\PasskeyChallengeStore::class,
            ],
        ],
    ],
];

