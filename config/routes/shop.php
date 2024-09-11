<?php

declare(strict_types=1);

use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routes): void {
    $routes->add('webgriffe_sylius_pausepay_plugin_payment_process', '/pausepay/{payumToken}/process')
        ->controller(['webgriffe_sylius_pausepay.controller.payment', 'processAction'])
        ->methods(['GET'])
    ;
};
