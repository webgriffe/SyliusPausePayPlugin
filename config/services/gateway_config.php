<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Payum\Core\Bridge\Symfony\Builder\GatewayFactoryBuilder;
use Webgriffe\SyliusPausePayPlugin\Payum\PausePayApi;
use Webgriffe\SyliusPausePayPlugin\Payum\PausePayGatewayFactory;

return static function (ContainerConfigurator $containerConfigurator) {
    $services = $containerConfigurator->services();

    $services->set('webgriffe_sylius_pausepay.gateway_factory_builder', GatewayFactoryBuilder::class)
        ->args([PausePayGatewayFactory::class,])
        ->tag('payum.gateway_factory_builder', ['factory' => PausePayApi::GATEWAY_CODE])
    ;

};
