<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use GuzzleHttp\Client as GuzzleHttpClient;
use Webgriffe\SyliusPausePayPlugin\Client\Client;
use Webgriffe\SyliusPausePayPlugin\Client\ClientInterface;

return static function (ContainerConfigurator $containerConfigurator) {
    $services = $containerConfigurator->services();

    $containerConfigurator->parameters()
        ->set('webgriffe_sylius_pausepay.client.production_url', 'https://api.pausepay.it')
        ->set('webgriffe_sylius_pausepay.client.sandbox_url', 'https://test-api.pausepay.it')
        ->set('webgriffe_sylius_pausepay.client.sandbox_api_key', '4a7c454e958faa2ee9e368fc97c8aba14c93414d349cf3f82a6403b206470cfe')
    ;

    $services->set('webgriffe_sylius_pausepay.http_client', GuzzleHttpClient::class);

    $services->set('webgriffe_sylius_pausepay.client.client', Client::class)
        ->args(
            [
                service('webgriffe_sylius_pausepay.http_client'),
                service('webgriffe_sylius_pausepay.provider.configuration'),
                service('webgriffe_sylius_pausepay.logger'),
                param('webgriffe_sylius_pausepay.client.production_url'),
                param('webgriffe_sylius_pausepay.client.sandbox_url'),
                param('webgriffe_sylius_pausepay.client.sandbox_api_key'),
            ]
        );

    $services->alias(ClientInterface::class, 'webgriffe_sylius_pausepay.client.client');
};
