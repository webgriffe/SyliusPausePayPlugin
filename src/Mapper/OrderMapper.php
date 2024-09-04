<?php

declare(strict_types=1);

namespace Webgriffe\SyliusPausePayPlugin\Mapper;

use DateTimeImmutable;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Webgriffe\SyliusPausePayPlugin\Client\ValueObject\Order;
use Webgriffe\SyliusPausePayPlugin\Client\ValueObject\OrderItem;
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

        $issueDate = new DateTimeImmutable();

        $items = [];
        foreach ($order->getItems() as $orderItem) {
            $itemName = $orderItem->getVariantName();
            if ($itemName === null) {
                $itemName = $orderItem->getProductName();
            }

            $items[] = new OrderItem(
                $itemName ?? '',
                $orderItem->getQuantity(),
                ($orderItem->getTotal() / $orderItem->getQuantity()) / 100,
            );
        }

        $shippingTotal = $order->getShippingTotal();
        if ($shippingTotal > 0) {
            $items[] = new OrderItem('Shipping', 1, $shippingTotal / 100);
        }

        return new Order(
            $amount / 100,
            $number,
            $issueDate,
            '', // todo
            '', // todo
            'https://ok', // todo
            'https://ko', // todo
            'Webgriffe SRL', // todo
            '02277170359', // todo
            'support@webgriffe.com',
            $items,
        );
    }
}