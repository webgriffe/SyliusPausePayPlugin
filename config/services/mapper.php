<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Webgriffe\SyliusPausePayPlugin\Mapper\OrderMapper;

return static function (ContainerConfigurator $containerConfigurator) {
    $services = $containerConfigurator->services();

    $services->set('webgriffe_sylius_pausepay.mapper.order', OrderMapper::class)
        ->args(
            [
                service('webgriffe_sylius_pausepay.resolver.company_info'),
                service('webgriffe_sylius_pausepay.resolver.number'),
            ]
        );
};
