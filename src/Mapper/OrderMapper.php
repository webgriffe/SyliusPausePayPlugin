<?php

declare(strict_types=1);

namespace Webgriffe\SyliusPausePayPlugin\Mapper;

use Carbon\Carbon;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Webgriffe\SyliusPausePayPlugin\Client\ValueObject\Order;
use Webgriffe\SyliusPausePayPlugin\Client\ValueObject\OrderItem;
use Webgriffe\SyliusPausePayPlugin\Resolver\CompanyInfoResolverInterface;
use Webmozart\Assert\Assert;

final class OrderMapper implements OrderMapperInterface
{
    public function __construct(private CompanyInfoResolverInterface $companyInfoResolver)
    {
    }

    public function mapFromSyliusPayment(PaymentInterface $payment, string $captureUrl, string $cancelUrl): Order
    {
        // todo: there's english text that should be translated
        $order = $payment->getOrder();
        Assert::isInstanceOf($order, OrderInterface::class);

        $amount = $payment->getAmount();
        Assert::notNull($amount);
        Assert::greaterThan($amount, 0);

        $number = $order->getNumber();
        Assert::stringNotEmpty($number);

        $issueDate = Carbon::now();

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

        $description = $this->getOrderDescription($order, $number);
        $companyInfo = $this->companyInfoResolver->resolveFromOrder($order);

        return new Order(
            $amount / 100,
            $number,
            $issueDate,
            $description,
            $description,
            $captureUrl,
            $cancelUrl,
            $companyInfo->getName(),
            $companyInfo->getVatNumber(),
            $companyInfo->getEmail(),
            $items,
        );
    }

    private function getOrderDescription(OrderInterface $order, string $number): string
    {
        $createdAt = $order->getCheckoutCompletedAt();
        Assert::notNull($createdAt);

        $descriptor = sprintf('Order #%s of %s', $number, $createdAt->format('Y-m-d'), );
        $hostname = $order->getChannel()?->getHostname();
        if ($hostname !== null) {
            $descriptor .= ' on ' . $hostname;
        }

        return $descriptor;
    }
}
