<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Webgriffe\SyliusPausePayPlugin\Entity\PaymentOrder;
use Webgriffe\SyliusPausePayPlugin\Factory\PaymentOrderFactory;
use Webgriffe\SyliusPausePayPlugin\Factory\PaymentOrderFactoryInterface;

return static function (ContainerConfigurator $containerConfigurator) {
    $services = $containerConfigurator->services();

    $containerConfigurator->parameters()
        ->set('webgriffe_sylius_pausepay.payment_order.class', PaymentOrder::class)
    ;

    $services->set('webgriffe_sylius_pausepay.factory.payment_order', PaymentOrderFactory::class)
        ->args([
            param('webgriffe_sylius_pausepay.payment_order.class'),
        ])
    ;

    $services->alias(PaymentOrderFactoryInterface::class, 'webgriffe_sylius_pausepay.factory.payment_order');
};
