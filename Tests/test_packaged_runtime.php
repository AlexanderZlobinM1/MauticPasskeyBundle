<?php

declare(strict_types=1);

$bundle = file_get_contents(dirname(__DIR__).'/MauticPasskeyBundle.php');
if (false === $bundle
    || !str_contains($bundle, "__DIR__.'/vendor/autoload.php'")
    || !str_contains($bundle, 'AttestationStatementSupportManager::class')
) {
    fwrite(STDERR, "Plugin-local production autoloader bootstrap is missing.\n");
    exit(1);
}

$lock = dirname(__DIR__).'/release-runtime/composer.lock';
if (!is_file($lock)) {
    fwrite(STDERR, "The release runtime lock is required for reproducible packages.\n");
    exit(1);
}

$data = json_decode((string) file_get_contents($lock), true, 512, JSON_THROW_ON_ERROR);
$packages = array_column($data['packages'] ?? [], 'name');
if (!in_array('web-auth/webauthn-lib', $packages, true)) {
    fwrite(STDERR, "Locked WebAuthn runtime dependency is missing.\n");
    exit(1);
}

echo "Packaged runtime contract: OK\n";
