<?php

declare(strict_types=1);

use Payum\Bundle\PayumBundle\Controller\NotifyController;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routes): void {
    $routes->add('payum_notify_do_unsafe', '/payment/notify/unsafe/{gateway}')
        ->controller([NotifyController::class, 'doUnsafeAction'])
        ->methods(['GET', 'POST'])
    ;
};
