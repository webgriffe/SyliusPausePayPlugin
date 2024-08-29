<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Webgriffe\SyliusPausePayPlugin\Form\Type\SyliusPausePayGatewayConfigurationType;
use Webgriffe\SyliusPausePayPlugin\Payum\PausePayApi;

return static function (ContainerConfigurator $containerConfigurator) {
    $services = $containerConfigurator->services();

    $services->set('webgriffe_sylius_pausepay.form.type.gateway_configuration', SyliusPausePayGatewayConfigurationType::class)
        ->tag('sylius.gateway_configuration_type', ['type' => PausePayApi::GATEWAY_CODE, 'label' => 'PausePay'])
        ->tag('form.type')
    ;
};
