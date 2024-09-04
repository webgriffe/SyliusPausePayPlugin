<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusPausePayPlugin\Unit\Mapper;

use Carbon\Carbon;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Sylius\Bundle\PayumBundle\Model\GatewayConfig;
use Sylius\Component\Core\Model\Adjustment;
use Sylius\Component\Core\Model\AdjustmentInterface;
use Sylius\Component\Core\Model\Channel;
use Sylius\Component\Core\Model\Customer;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\Order;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\OrderItem;
use Sylius\Component\Core\Model\OrderItemInterface;
use Sylius\Component\Core\Model\OrderItemUnit;
use Sylius\Component\Core\Model\Payment;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Core\Model\PaymentMethod;
use Sylius\Component\Core\OrderPaymentStates;
use Sylius\Component\Order\Model\OrderItemUnitInterface;
use Tests\Webgriffe\SyliusPausePayPlugin\Service\Resolver\DummyCompanyInfoResolver;
use Webgriffe\SyliusPausePayPlugin\Mapper\OrderMapper;
use Webgriffe\SyliusPausePayPlugin\Mapper\OrderMapperInterface;

final class OrderMapperTest extends TestCase
{
    private OrderMapperInterface $mapper;

    protected function setUp(): void
    {
        $this->mapper = new OrderMapper(new DummyCompanyInfoResolver());
        Carbon::setTestNow('2024-09-01 12:30:00');
    }

    public function test_it_maps_sylius_payment_to_pausepay_order(): void
    {
        $payment = $this->getPayment();

        $order = $this->mapper->mapFromSyliusPayment($payment, 'https://ok', 'https://ko');

        self::assertSame(260.62, $order->getAmount());
        self::assertSame('000001732', $order->getNumber());
        self::assertSame('2024-09-01 12:30:00', $order->getIssueDate()->format('Y-m-d H:i:s'));
        self::assertSame('Order #000001732 of 2024-09-01 on mywebsite.com', $order->getDescription());
        self::assertSame('Order #000001732 of 2024-09-01 on mywebsite.com', $order->getRemittance());
        self::assertSame('https://ok', $order->getOkRedirectUrl());
        self::assertSame('https://ko', $order->getKoRedirectUrl());
        self::assertSame('Webgriffe SRL', $order->getBuyerInfoName());
        self::assertSame('02277170359', $order->getBuyerInfoVatNumber());
        self::assertSame('support@webgriffe.com', $order->getBuyerInfoEmail());

        $items = $order->getPurchasedItems();
        self::assertCount(3, $items);

        self::assertSame($items[0]->getName(), 'Star Wars Mug');
        self::assertSame($items[0]->getQuantity(), 2);
        self::assertSame($items[0]->getAmount(), 61.81);

        self::assertSame($items[1]->getName(), 'Obi Wan Kenobi action figure');
        self::assertSame($items[1]->getQuantity(), 1);
        self::assertSame($items[1]->getAmount(), 122.0);

        self::assertSame($items[2]->getName(), 'Shipping');
        self::assertSame($items[2]->getQuantity(), 1);
        self::assertSame($items[2]->getAmount(), 15.0);
    }

    private function getPayment(): PaymentInterface
    {
        $payment = new Payment();
        $payment->setCurrencyCode('EUR');

        $paymentMethod = new PaymentMethod();
        $paymentMethod->setCode('pausepay');
        $paymentMethod->setGatewayConfig(new GatewayConfig());
        $payment->setMethod($paymentMethod);

        $order = $this->getOrder();
        $payment->setOrder($order);

        $payment->setAmount($order->getTotal());

        return $payment;
    }

    private function getOrder(): OrderInterface
    {
        $order = new Order();
        $order->setNumber('000001732');
        $order->setPaymentState(OrderPaymentStates::STATE_AWAITING_PAYMENT);
        $order->setCheckoutCompletedAt(new DateTimeImmutable('2024-09-01 10:00:10'));
        $order->setPaymentState(OrderPaymentStates::STATE_PAID);

        $channel = new Channel();
        $channel->setHostname('mywebsite.com');
        $order->setChannel($channel);

        $order->setCustomer($this->getCustomer());

        $shippingCost = 1230;
        $order->addAdjustment($this->getShippingAdjustment($shippingCost));
        $order->addAdjustment($this->getTaxAdjustment((int) (round($shippingCost * 0.22, 2))));

        $order->addItem($this->getOrderItemWithUnit('Star Wars Mug', 5067, 2));
        $order->addItem($this->getOrderItemWithUnit('Obi Wan Kenobi action figure', 10000, 1));
        $this->addTaxesToOrderItems($order);

        return $order;
    }

    private function getCustomer(): CustomerInterface
    {
        $customer = new Customer();
        $customer->setFirstName('Oliver ABC');
        $customer->setLastName('Queen DEF');
        $customer->setEmail('ufficio.amministrazionetlasrl@gmail.com');

        return $customer;
    }

    private function getOrderItemWithUnit(string $name, int $unitPrice, int $quantity): OrderItem
    {
        $orderItem = new OrderItem();
        $orderItem->setUnitPrice($unitPrice);
        $orderItem->setVariantName($name);
        for ($i = 0; $i < $quantity; ++$i) {
            new OrderItemUnit($orderItem);
        }

        return $orderItem;
    }

    private function getShippingAdjustment(int $amount): AdjustmentInterface
    {
        $adjustment = new Adjustment();
        $adjustment->setType(AdjustmentInterface::SHIPPING_ADJUSTMENT);
        $adjustment->setAmount($amount);
        $adjustment->setNeutral(false);

        return $adjustment;
    }

    private function getTaxAdjustment(int $amount): AdjustmentInterface
    {
        $adjustment = new Adjustment();
        $adjustment->setType(AdjustmentInterface::TAX_ADJUSTMENT);
        $adjustment->setAmount($amount);
        $adjustment->setNeutral(false);

        return $adjustment;
    }

    private function addTaxesToOrderItems(OrderInterface $order, float $taxRate = 0.22): void
    {
        /** @var OrderItemInterface $item */
        foreach ($order->getItems() as $item) {
            $itemTaxTotal = round($item->getTotal() * $taxRate);
            /** @var OrderItemUnitInterface $unit */
            foreach ($item->getUnits() as $unit) {
                $unit->addAdjustment($this->getTaxAdjustment((int) ($itemTaxTotal / count($item->getUnits()))));
            }
        }
    }
}
