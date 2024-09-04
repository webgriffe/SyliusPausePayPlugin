<?php

declare(strict_types=1);

namespace Webgriffe\SyliusPausePayPlugin\Provider;

use InvalidArgumentException;
use Sylius\Bundle\PayumBundle\Model\GatewayConfigInterface;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\PaymentMethodInterface;
use Sylius\Component\Core\Repository\PaymentMethodRepositoryInterface;
use Webgriffe\SyliusPausePayPlugin\Payum\PausePayApi;

final class ConfigurationProvider implements ConfigurationProviderInterface
{
    public function __construct(
        private PaymentMethodRepositoryInterface $paymentMethodRepository,
        private ChannelContextInterface $channelContext,
    ) {
    }

    public function getApiKey(): string
    {
        $config = $this->getConfig();
        $apiKey = $config[PausePayApi::API_KEY_FIELD_NAME] ?? null;
        if (!is_string($apiKey)) {
            throw new InvalidArgumentException('No PausePay sandbox configuration found');
        }

        return $apiKey;
    }

    public function isSandbox(): bool
    {
        $config = $this->getConfig();
        $isSandbox = $config[PausePayApi::SANDBOX_FIELD_NAME] ?? null;
        if (!is_bool($isSandbox)) {
            throw new InvalidArgumentException('No PausePay sandbox configuration found');
        }

        return $isSandbox;
    }

    private function getConfig(): array
    {
        /** @var ChannelInterface $channel */
        $channel = $this->channelContext->getChannel();
        $methods = $this->paymentMethodRepository->findEnabledForChannel($channel);

        /** @var PaymentMethodInterface $method */
        foreach ($methods as $method) {
            /** @var GatewayConfigInterface $gatewayConfig */
            $gatewayConfig = $method->getGatewayConfig();
            /** @psalm-suppress DeprecatedMethod */
            if ($gatewayConfig->getFactoryName() !== PausePayApi::GATEWAY_CODE) {
                continue;
            }

            return $gatewayConfig->getConfig();
        }

        throw new InvalidArgumentException('No PausePay payment method defined');
    }
}
