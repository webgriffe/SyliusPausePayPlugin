<?php

declare(strict_types=1);

namespace Webgriffe\SyliusPausePayPlugin\Mapper;

use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Webgriffe\SyliusPausePayPlugin\Client\ValueObject\Order;
use Webmozart\Assert\Assert;

final class OrderMapper implements OrderMapperInterface
{
    public function mapFromSyliusPayment(PaymentInterface $payment): Order
    {
        $order = $payment->getOrder();
        Assert::isInstanceOf($order, OrderInterface::class);

        $amount = $payment->getAmount();
        Assert::notNull($amount);
        Assert::greaterThan($amount, 0);

        $number = $order->getNumber();
        Assert::stringNotEmpty($number);

        return new Order($amount, $number);
    }
}
