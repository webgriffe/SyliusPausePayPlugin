<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Webgriffe\SyliusPausePayPlugin\Checker\PausePayAvailabilityChecker;

return static function (ContainerConfigurator $containerConfigurator) {
    $services = $containerConfigurator->services();

    $services->set('webgriffe_sylius_pausepay.checker.pause_pay_availability', PausePayAvailabilityChecker::class)
        ->args([
            service('event_dispatcher'),
        ]);
};
