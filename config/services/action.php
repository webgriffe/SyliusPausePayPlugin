<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Webgriffe\SyliusPausePayPlugin\Payum\Action\CaptureAction;
use Webgriffe\SyliusPausePayPlugin\Payum\Action\StatusAction;
use Webgriffe\SyliusPausePayPlugin\Payum\PausePayApi;

return static function (ContainerConfigurator $containerConfigurator) {
    $services = $containerConfigurator->services();

    $services->set('webgriffe_sylius_pausepay.payum.action.capture', CaptureAction::class)
        ->public()
        ->args(
            [
                service('router'),
                service('webgriffe_sylius_pausepay.logger'),
                service('request_stack'),
                service('webgriffe_sylius_pausepay.client'),
            ]
        )
        ->tag('payum.action', ['factory' => PausePayApi::GATEWAY_CODE, 'alias' => 'payum.action.capture'])
        ->tag('payum.action', ['factory' => PausePayApi::GATEWAY_CODE, 'alias' => 'payum.action.capture']);

    $services->set('webgriffe_sylius_pausepay.payum.action.status', StatusAction::class)
        ->public()
        ->args(
            [
                service('webgriffe_sylius_pausepay.logger'),
            ]
        );
};
