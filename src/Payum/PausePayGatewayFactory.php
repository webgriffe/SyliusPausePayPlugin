<?php

declare(strict_types=1);

namespace Webgriffe\SyliusPausePayPlugin\Payum;

use ArrayObject;
use Payum\Core\Bridge\Spl\ArrayObject as PayumArrayObject;
use Payum\Core\GatewayFactory;

final class PausePayGatewayFactory extends GatewayFactory
{
    protected function populateConfig(PayumArrayObject $config): void
    {
        $config->defaults(
            [
                'payum.factory_name' => PausePayApi::GATEWAY_CODE,
                'payum.factory_title' => 'PausePay',
                'payum.action.status' => '@webgriffe_sylius_pausepay.payum.action.status',
            ],
        );

        if (false === (bool) $config['payum.api']) {
            $defaultOptions = [PausePayApi::SANDBOX_FIELD_NAME => true];
            $config->defaults($defaultOptions);
            $config['payum.default_options'] = $defaultOptions;
            $config['payum.required_options'] = [
                PausePayApi::API_KEY_FIELD_NAME,
                PausePayApi::SANDBOX_FIELD_NAME,
            ];

            /**
             * @psalm-suppress MixedArgumentTypeCoercion
             *
             * @phpstan-ignore-next-line
             */
            $config['payum.api'] = static fn (ArrayObject $config): PausePayApi => new PausePayApi((array) $config);
        }
    }
}
