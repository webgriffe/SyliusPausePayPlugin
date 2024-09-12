<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Webgriffe\SyliusPausePayPlugin\Controller\PaymentController;
use Webgriffe\SyliusPausePayPlugin\Controller\WebhookController;

return static function (ContainerConfigurator $containerConfigurator) {
    $services = $containerConfigurator->services();

    $services->set('webgriffe_sylius_pausepay.controller.payment', PaymentController::class)
        ->args([
            service('payum.security.token_storage'),
            service('router'),
            service('sylius.repository.payment'),
        ])
        ->call('setContainer', [service('service_container')])
        ->tag('controller.service_arguments')
    ;
};
