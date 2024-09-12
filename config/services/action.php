<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Webgriffe\SyliusPausePayPlugin\Payum\Action\CancelAction;
use Webgriffe\SyliusPausePayPlugin\Payum\Action\CaptureAction;
use Webgriffe\SyliusPausePayPlugin\Payum\Action\NotifyAction;
use Webgriffe\SyliusPausePayPlugin\Payum\Action\NotifyNullAction;
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
                service('webgriffe_sylius_pausepay.client.client'),
                service('webgriffe_sylius_pausepay.mapper.order'),
                service('webgriffe_sylius_pausepay.repository.payment_order'),
                service('webgriffe_sylius_pausepay.factory.payment_order'),
            ]
        )
        ->tag('payum.action', ['factory' => PausePayApi::GATEWAY_CODE, 'alias' => 'payum.action.capture']);

    $services->set('webgriffe_sylius_pausepay.payum.action.status', StatusAction::class)
        ->public()
        ->args(
            [
                service('webgriffe_sylius_pausepay.logger'),
            ]
        );

    $services->set('webgriffe_sylius_pausepay.payum.action.cancel', CancelAction::class)
        ->public()
        ->args([
            service('webgriffe_sylius_pausepay.logger'),
            service('router'),
        ])
        ->tag('payum.action', ['factory' => PausePayApi::GATEWAY_CODE, 'alias' => 'payum.action.cancel']);

    $services->set('webgriffe_sylius_pausepay.payum.action.notify_null', NotifyNullAction::class)
        ->public()
        ->args([
            service('serializer'),
            service('webgriffe_sylius_pausepay.repository.payment_order'),
            service('webgriffe_sylius_pausepay.logger'),
        ])
        ->tag('payum.action', ['factory' => PausePayApi::GATEWAY_CODE, 'alias' => 'payum.action.notify_null']);

    $services->set('webgriffe_sylius_pausepay.payum.action.notify', NotifyAction::class)
        ->public()
        ->args([
            service('serializer'),
            service('webgriffe_sylius_pausepay.logger'),
        ])
        ->tag('payum.action', ['factory' => PausePayApi::GATEWAY_CODE, 'alias' => 'payum.action.notify']);

};
