<?php

declare(strict_types=1);

namespace Webgriffe\SyliusPausePayPlugin\Checker;

use Sylius\Component\Core\Model\AddressInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Core\Model\PaymentMethodInterface;
use Sylius\Component\Payment\Model\PaymentInterface as BasePaymentInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Webgriffe\SyliusPausePayPlugin\Payum\PausePayApi;
use Webmozart\Assert\Assert;

final class PausePayAvailabilityChecker implements PaymentMethodAvailabilityCheckerInterface
{
    public const MINIMUM_ORDER_AMOUNT = 50000;

    public const MAXIMUM_ORDER_AMOUNT = 2000000;

    public const AVAILABILITY_EVENT_NAME = 'webgriffe.sylius_pausepay_plugin.checker.pausepay_availability';

    public function __construct(private EventDispatcherInterface $eventDispatcher)
    {
    }

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

        $isAvailable = $orderAmount >= self::MINIMUM_ORDER_AMOUNT && $orderAmount <= self::MAXIMUM_ORDER_AMOUNT;

        /** @var GenericEvent $event */
        $event = $this->eventDispatcher->dispatch(
            new GenericEvent($order, ['paymentMethod' => $paymentMethod, 'isAvailable' => $isAvailable]),
            self::AVAILABILITY_EVENT_NAME,
        );

        /** @var mixed $result */
        $result = $event->getArgument('isAvailable');
        if (is_bool($result)) {
            return $result;
        }

        return $isAvailable;
    }
}
