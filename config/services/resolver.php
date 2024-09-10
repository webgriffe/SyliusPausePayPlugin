<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Webgriffe\SyliusPausePayPlugin\Mapper\NumberResolver;
use Webgriffe\SyliusPausePayPlugin\Resolver\CompanyInfoResolver;
use Webgriffe\SyliusPausePayPlugin\Resolver\PausePayPaymentMethodsResolver;

return static function (ContainerConfigurator $containerConfigurator) {
    $services = $containerConfigurator->services();

    $services->set('webgriffe_sylius_pausepay.resolver.company_info', CompanyInfoResolver::class);

    $services->set('webgriffe_sylius_pausepay.resolver.number', NumberResolver::class);

    $services->set('webgriffe_sylius_pausepay.resolver.payment_methods', PausePayPaymentMethodsResolver::class)
        ->args([service('sylius.repository.payment_method'),])
        ->tag('sylius.payment_method_resolver', [
            'type' => 'pausepay',
            'label' => 'PausePay',
            'priority' => 2,
        ]);
};
