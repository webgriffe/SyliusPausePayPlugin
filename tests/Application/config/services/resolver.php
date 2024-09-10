<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Tests\Webgriffe\SyliusPausePayPlugin\App\Resolver\CompanyInfoResolver;
use Tests\Webgriffe\SyliusPausePayPlugin\App\Resolver\NumberResolver;

return static function (ContainerConfigurator $containerConfigurator) {
    $services = $containerConfigurator->services();

    $services->set('webgriffe_sylius_pausepay.resolver.company_info', CompanyInfoResolver::class);
    $services->set('webgriffe_sylius_pausepay.resolver.number', NumberResolver::class);
};
