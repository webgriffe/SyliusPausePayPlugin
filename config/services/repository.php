<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Webgriffe\SyliusPausePayPlugin\Doctrine\ORM\PaymentOrderRepository;
use Webgriffe\SyliusPausePayPlugin\Repository\PaymentOrderRepositoryInterface;

return static function (ContainerConfigurator $containerConfigurator) {
    $services = $containerConfigurator->services();

    $services->set('webgriffe_sylius_pausepay.repository.payment_order', PaymentOrderRepository::class)
        ->args([service('doctrine'),])
        ->tag('doctrine.repository_service');

    $services->alias(PaymentOrderRepositoryInterface::class, 'webgriffe_sylius_pausepay.repository.payment_order');
};
