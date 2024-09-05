<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Webgriffe\SyliusPausePayPlugin\Client\Client;
use Webgriffe\SyliusPausePayPlugin\Provider\ConfigurationProvider;

return static function (ContainerConfigurator $containerConfigurator) {
    $services = $containerConfigurator->services();

    $services->set('webgriffe_sylius_pausepay.provider.configuration', ConfigurationProvider::class)
        ->args(
            [
                service('sylius.repository.payment_method'),
                service('sylius.context.channel'),
            ]
        );
};
