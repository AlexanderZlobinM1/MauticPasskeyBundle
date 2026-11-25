<?php

declare(strict_types=1);

namespace MauticPlugin\MauticPasskeyBundle;

use Mautic\PluginBundle\Bundle\PluginBundleBase;

// MCC/MCD installs plugins as standalone archives rather than as root Composer
// requirements. Load dependencies bundled with the release artifact while
// continuing to use Mautic's root autoloader when they are already available.
if (!class_exists(\Webauthn\AttestationStatement\AttestationStatementSupportManager::class)) {
    $pluginAutoloader = __DIR__.'/vendor/autoload.php';
    if (is_file($pluginAutoloader)) {
        $pluginLoader = require_once $pluginAutoloader;
        if ($pluginLoader instanceof \Composer\Autoload\ClassLoader) {
            // Mautic's root dependencies remain authoritative. The plugin
            // loader supplies only classes the root application does not have.
            $pluginLoader->unregister();
            $pluginLoader->register(false);
        }
    }
}

final class MauticPasskeyBundle extends PluginBundleBase
{
}

