<?php

declare(strict_types=1);

use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routes): void {
    $routes->add('webgriffe_sylius_pausepay_plugin_payment_status', '/pausepay/{payumToken}/status')
        ->controller(['webgriffe_sylius_pausepay.controller.payment', 'statusAction'])
        ->methods(['GET'])
    ;

    $routes->add('webgriffe_sylius_pausepay_plugin_payment_webhook', '/pausepay/webhook')
        ->controller(['webgriffe_sylius_pausepay.controller.webhook', 'indexAction'])
        ->methods(['POST'])
    ;
};
