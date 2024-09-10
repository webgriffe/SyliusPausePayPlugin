<?php

declare(strict_types=1);

namespace Webgriffe\SyliusPausePayPlugin\Resolver;

use Sylius\Component\Core\Model\AddressInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Core\Model\PaymentMethodInterface;
use Sylius\Component\Core\Repository\PaymentMethodRepositoryInterface;
use Sylius\Component\Payment\Model\PaymentInterface as BasePaymentInterface;
use Sylius\Component\Payment\Resolver\PaymentMethodsResolverInterface;
use Webgriffe\SyliusPausePayPlugin\Payum\PausePayApi;
use Webmozart\Assert\Assert;

final class PausePayPaymentMethodsResolver implements PaymentMethodsResolverInterface
{
    public function __construct(
        private PaymentMethodRepositoryInterface $paymentMethodRepository,
    ) {
    }

    /**
     * @param BasePaymentInterface|PaymentInterface $subject
     *
     * @return PaymentMethodInterface[]
     */
    public function getSupportedMethods(BasePaymentInterface $subject): array
    {
        Assert::true($this->supports($subject), 'This payment method is not support by resolver');
        Assert::isInstanceOf($subject, PaymentInterface::class);
        $order = $subject->getOrder();
        Assert::isInstanceOf($order, OrderInterface::class);
        $channel = $order->getChannel();
        Assert::isInstanceOf($channel, ChannelInterface::class);
        $billingAddress = $order->getBillingAddress();
        Assert::isInstanceOf($billingAddress, AddressInterface::class);
        // todo: should we check the shipping address too?
        $currencyCode = $order->getCurrencyCode();
        Assert::notNull($currencyCode);
        $orderAmount = $order->getTotal();

        /** @var PaymentMethodInterface[] $paymentMethods */
        $paymentMethods = $this->paymentMethodRepository->findEnabledForChannel($channel);

        return array_filter(
            $paymentMethods,
            static function (PaymentMethodInterface $paymentMethod) use (
                $billingAddress,
                $currencyCode,
                $orderAmount
            ) {
                $gatewayConfig = $paymentMethod->getGatewayConfig();
                if ($gatewayConfig === null) {
                    return false;
                }
                /** @psalm-suppress DeprecatedMethod */
                if ($gatewayConfig->getFactoryName() !== PausePayApi::GATEWAY_CODE) {
                    return true;
                }
                if ($billingAddress->getCountryCode() !== 'IT') {
                    return false;
                }
                if ($currencyCode !== 'EUR') {
                    return false;
                }
                if ($orderAmount < 50000 || $orderAmount > 2000000) {
                    return false;
                }

                return true;
            },
        );
    }

    public function supports(BasePaymentInterface $subject): bool
    {
        if (!$subject instanceof PaymentInterface) {
            return false;
        }
        $order = $subject->getOrder();
        if (!$order instanceof OrderInterface) {
            return false;
        }
        $channel = $order->getChannel();
        if (!$channel instanceof ChannelInterface) {
            return false;
        }
        $paymentMethod = $subject->getMethod();
        if (!$paymentMethod instanceof PaymentMethodInterface) {
            return false;
        }
        $billingAddress = $order->getBillingAddress();
        if (!$billingAddress instanceof AddressInterface) {
            return false;
        }
        $currencyCode = $order->getCurrencyCode();

        return $currencyCode !== null;
    }
}
