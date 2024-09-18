<?php

declare(strict_types=1);

namespace Webgriffe\SyliusPausePayPlugin\Checker;

use Sylius\Component\Core\Model\AddressInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Core\Model\PaymentMethodInterface;
use Sylius\Component\Payment\Model\PaymentInterface as BasePaymentInterface;
use Webgriffe\SyliusPausePayPlugin\Payum\PausePayApi;
use Webmozart\Assert\Assert;

final class PausePayAvailabilityChecker implements PaymentMethodAvailabilityCheckerInterface
{
    public function isAvailable(BasePaymentInterface $subject, PaymentMethodInterface $paymentMethod): bool
    {
        $gatewayConfig = $paymentMethod->getGatewayConfig();
        if ($gatewayConfig === null) { // we cannot be sure that it's PausePay payment method, so it is available
            return true;
        }

        /** @psalm-suppress DeprecatedMethod */
        if ($gatewayConfig->getFactoryName() !== PausePayApi::GATEWAY_CODE) {
            return true;
        }

        Assert::isInstanceOf($subject, PaymentInterface::class);
        $order = $subject->getOrder();
        Assert::isInstanceOf($order, OrderInterface::class);

        $billingAddress = $order->getBillingAddress();
        Assert::isInstanceOf($billingAddress, AddressInterface::class);
        if ($billingAddress->getCountryCode() !== 'IT') {
            return false;
        }

        $currencyCode = $order->getCurrencyCode();
        Assert::notNull($currencyCode);
        if ($currencyCode !== 'EUR') {
            return false;
        }

        $orderAmount = $order->getTotal();

        return $orderAmount >= 50000 && $orderAmount <= 2000000;
    }
}
