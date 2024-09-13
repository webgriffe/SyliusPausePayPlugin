<?php

declare(strict_types=1);

namespace Webgriffe\SyliusPausePayPlugin\Mapper;

use Carbon\Carbon;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Webgriffe\SyliusPausePayPlugin\Client\ValueObject\Order;
use Webgriffe\SyliusPausePayPlugin\Client\ValueObject\OrderItem;
use Webgriffe\SyliusPausePayPlugin\Resolver\CompanyInfoResolverInterface;
use Webgriffe\SyliusPausePayPlugin\Resolver\NumberResolverInterface;
use Webmozart\Assert\Assert;

final class OrderMapper implements OrderMapperInterface
{
    public function __construct(
        private CompanyInfoResolverInterface $companyInfoResolver,
        private NumberResolverInterface $numberResolver,
    ) {
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

            // todo: with the rounding we are losing some cents, we should compensate this
            $items[] = new OrderItem(
                $itemName ?? '',
                $orderItem->getQuantity(),
                $this->formatPriceForPausepay($orderItem->getTotal() / $orderItem->getQuantity()),
            );
        }

        $shippingTotal = $order->getShippingTotal();
        if ($shippingTotal > 0) {
            $items[] = new OrderItem('Shipping', 1, $this->formatPriceForPausepay($shippingTotal));
        }

        $description = $this->getOrderDescription($order, $number);
        $companyInfo = $this->companyInfoResolver->resolveFromOrder($order);

        return new Order(
            $this->formatPriceForPausepay($amount),
            $this->numberResolver->resolveFromOrder($order),
            $issueDate,
            $description,
            $description,
            $captureUrl,
            $cancelUrl,
            $companyInfo->getName(),
            $companyInfo->getVatNumber(),
            $companyInfo->getEmail(),
            $companyInfo->getPec(),
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

    private function formatPriceForPausepay(float|int $orderItemUnitPrice): float
    {
        return round($orderItemUnitPrice / 100, 2);
    }
}
