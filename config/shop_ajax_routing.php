<?php

declare(strict_types=1);

use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routes): void {
    $routes->add('webgriffe_sylius_pausepay_plugin_payment_status', '/payment/{payumToken}/pausepay-status')
        ->controller(['webgriffe_sylius_pausepay.controller.payment', 'statusAction'])
        ->methods(['GET'])
    ;
};
