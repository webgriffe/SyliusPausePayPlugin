<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use GuzzleHttp\Client as GuzzleHttpClient;
use Webgriffe\SyliusPausePayPlugin\Client\Client;
use Webgriffe\SyliusPausePayPlugin\Client\ClientInterface;

return static function (ContainerConfigurator $containerConfigurator) {
    $services = $containerConfigurator->services();

    $services->set('webgriffe_sylius_pausepay.http_client', GuzzleHttpClient::class);

    $services->set('webgriffe_sylius_pausepay.client', Client::class)
        ->args(
            [
                service('webgriffe_sylius_pausepay.http_client'),
                service('webgriffe_sylius_pausepay.logger'),
            ]
        );

    $services->alias(ClientInterface::class, 'webgriffe_sylius_pausepay.client');
};
