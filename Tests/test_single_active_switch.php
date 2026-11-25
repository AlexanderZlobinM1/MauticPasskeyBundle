<?php

declare(strict_types=1);

$integration = (string) file_get_contents(dirname(__DIR__).'/Integration/MauticPasskeyIntegration.php');
$login       = (string) file_get_contents(dirname(__DIR__).'/EventListener/LoginButtonSubscriber.php');

foreach (['SHOW_LOGIN_BUTTON_FIELD', 'appendToForm', 'shouldShowLoginButton', 'getFormNotes', 'sso_service'] as $forbidden) {
    if (str_contains($integration, $forbidden)) {
        fwrite(STDERR, sprintf("Redundant integration control remains: %s\n", $forbidden));
        exit(1);
    }
}

if (!str_contains($integration, 'return [];')) {
    fwrite(STDERR, "Supported features must stay empty so Mautic renders no Features tab.\n");
    exit(1);
}

if (!str_contains($login, "getIntegrationSettings()->isPublished()")) {
    fwrite(STDERR, "Login rendering must remain governed by the global Active switch.\n");
    exit(1);
}

echo "Single Active switch contract: OK\n";
