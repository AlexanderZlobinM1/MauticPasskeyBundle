<?php

declare(strict_types=1);

$source = file_get_contents(__DIR__.'/../EventListener/LoginButtonSubscriber.php');
if (!is_string($source)) {
    throw new RuntimeException('Unable to read login button subscriber.');
}
$coordinator = file_get_contents(__DIR__.'/../Assets/js/auth-alternative-coordinator.js');
if (!is_string($coordinator)) {
    throw new RuntimeException('Unable to read shared alternative-auth coordinator.');
}
$source .= $coordinator;

$config = file_get_contents(__DIR__.'/../Config/config.php');
if (!is_string($config)) {
    throw new RuntimeException('Unable to read plugin configuration.');
}

$required = [
    'data-mautic-auth-alternative-block="1"',
    'data-mautic-auth-alternative="1"',
    'data-mautic-auth-separator-label',
    'data-mautic-auth-alternative-separator',
    'MauticAuthAlternativeCoordinator',
    'MutationObserver',
];

foreach ($required as $marker) {
    if (!str_contains($source, $marker)) {
        throw new RuntimeException(sprintf('Missing shared alternative-auth marker: %s', $marker));
    }
}

if (!str_contains($config, "'mautic.integration.mauticpasskey'")) {
    throw new RuntimeException('Integration service ID must match Mautic IntegrationHelper discovery.');
}

echo "Shared alternative-auth contract: OK\n";
