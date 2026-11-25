<?php

declare(strict_types=1);

use Mautic\CoreBundle\DependencyInjection\MauticCoreExtension;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return function (ContainerConfigurator $configurator): void {
    $services = $configurator->services()
        ->defaults()
        ->autowire()
        ->autoconfigure()
        ->public();

    $excludes = [
        'Assets',
        'DependencyInjection',
        'EventListener',
        'Integration',
        'Migrations',
        'Resources',
        'Tests',
        'Translations',
        'vendor',
        'compatibility',
    ];

    $services->alias('mauticpasskey.helper.encryption', \Mautic\CoreBundle\Helper\EncryptionHelper::class);

    $services->load('MauticPlugin\\MauticPasskeyBundle\\', '../')
        ->exclude('../{'.implode(',', array_merge(MauticCoreExtension::DEFAULT_EXCLUDES, $excludes)).'}');

    $services->load(
        'MauticPlugin\\MauticPasskeyBundle\\Entity\\',
        '../Entity/*Repository.php'
    );
};

