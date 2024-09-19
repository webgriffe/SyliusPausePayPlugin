<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Tests\Webgriffe\SyliusPausePayPlugin\App\EventListener\RedirectToPausePayEventListener;
use Webgriffe\SyliusPausePayPlugin\Payum\Action\CaptureAction;

return static function (ContainerConfigurator $containerConfigurator) {
    $services = $containerConfigurator->services();

    $services->set(
        'webgriffe_sylius_pausepay.event_listener.redirect_to_pausepay',
        RedirectToPausePayEventListener::class
    )
        ->args(
            [
                service('request_stack'),
                service('router'),
            ]
        )
        ->tag(
            'kernel.event_listener',
            [
                'event' => CaptureAction::DETAILS_ALREADY_POPULATED_EVENT_NAME,
                'method' => 'redirectToPausePayWhenFromMyAccount',
            ]
        );
};
