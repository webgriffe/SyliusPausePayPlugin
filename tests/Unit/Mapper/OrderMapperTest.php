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
use Webgriffe\SyliusPausePayPlugin\Mapper\NumberResolver;
use Webgriffe\SyliusPausePayPlugin\Mapper\OrderMapper;
use Webgriffe\SyliusPausePayPlugin\Mapper\OrderMapperInterface;

final class OrderMapperTest extends TestCase
{
    private OrderMapperInterface $mapper;

    protected function setUp(): void
    {
        $this->mapper = new OrderMapper(new DummyCompanyInfoResolver(), new NumberResolver());
        Carbon::setTestNow('2024-09-01 12:30:00');
    }

    public function test_it_maps_sylius_payment_to_pausepay_order(): void
    {
        $order = $this->getBaseOrder();
        $this->addShippingCost($order);
        $order->addItem($this->getOrderItemWithUnit('Star Wars Mug', 5067, 2));
        $order->addItem($this->getOrderItemWithUnit('Obi Wan Kenobi action figure', 10000, 1));
        $this->addTaxesToOrderItems($order);

        $pausePayOrder = $this->mapper->mapFromSyliusPayment($this->getPayment($order), 'https://ok', 'https://ko');

        self::assertSame(260.62, $pausePayOrder->getAmount());
        self::assertSame('000001732', $pausePayOrder->getNumber());
        self::assertSame('2024-09-01 12:30:00', $pausePayOrder->getIssueDate()->format('Y-m-d H:i:s'));
        self::assertSame('Order #000001732 of 2024-09-01 on mywebsite.com', $pausePayOrder->getDescription());
        self::assertSame('Order #000001732 of 2024-09-01 on mywebsite.com', $pausePayOrder->getRemittance());
        self::assertSame('https://ok', $pausePayOrder->getOkRedirectUrl());
        self::assertSame('https://ko', $pausePayOrder->getKoRedirectUrl());
        self::assertSame('Webgriffe SRL', $pausePayOrder->getBuyerInfoName());
        self::assertSame('02277170359', $pausePayOrder->getBuyerInfoVatNumber());
        self::assertSame('support@webgriffe.com', $pausePayOrder->getBuyerInfoEmail());
        self::assertSame('pec@webgriffe.com', $pausePayOrder->getBuyerInfoPec());

        $items = $pausePayOrder->getPurchasedItems();
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

    public function test_it_maps_sylius_payment_with_various_discounts_to_pausepay_order(): void
    {
        $order = $this->getBaseOrder();
        $this->addShippingCost($order);

        // order discount of 50€, so it is 50/5 = 10 per item
        $item1 = $this->getOrderItemWithUnit('Star Wars Mug', 5067, 2, 1203, 1000);
        $order->addItem($item1);
        $item2 = $this->getOrderItemWithUnit('Obi Wan Kenobi action figure', 10000, 3, 1000, 1000);
        $order->addItem($item2);
        $this->addTaxesToOrderItems($order);

        self::assertSame(37768, $order->getTotal()); // ((5067-1203-1000)*1.22)*2 + ((10000-1000-1000)*1.22)*3 + 1500

        $pausePayOrder = $this->mapper->mapFromSyliusPayment($this->getPayment($order), 'https://ok', 'https://ko');

        self::assertSame(377.68, $pausePayOrder->getAmount());

        $items = $pausePayOrder->getPurchasedItems();
        self::assertCount(3, $items);

        self::assertSame($items[0]->getName(), 'Star Wars Mug');
        self::assertSame($items[0]->getQuantity(), 2);
        self::assertSame($items[0]->getAmount(), 34.94); // (50.67-12.03-10.00)*1.22

        self::assertSame($items[1]->getName(), 'Obi Wan Kenobi action figure');
        self::assertSame($items[1]->getQuantity(), 3);
        self::assertSame($items[1]->getAmount(), 97.60); // (100.00-10.00-10.00)*1.22

        self::assertSame($items[2]->getName(), 'Shipping');
        self::assertSame($items[2]->getQuantity(), 1);
        self::assertSame($items[2]->getAmount(), 15.0);
    }

    public function test_it_maps_sylius_payment_with_maximum_two_decimals_on_amounts(): void
    {
        $order = $this->getBaseOrder();

        $orderItem = new OrderItem();
        $orderItem->setUnitPrice(8775);
        $orderItem->setVariantName('Star Wars Mug');
        $orderItemUnit = new OrderItemUnit($orderItem);
        $orderItemUnit->addAdjustment($this->getDiscountAdjustment(75, AdjustmentInterface::ORDER_UNIT_PROMOTION_ADJUSTMENT));
        $orderItemUnit->addAdjustment($this->getDiscountAdjustment(1000));
        $orderItemUnit = new OrderItemUnit($orderItem);
        $orderItemUnit->addAdjustment($this->getDiscountAdjustment(74, AdjustmentInterface::ORDER_UNIT_PROMOTION_ADJUSTMENT));
        $orderItemUnit->addAdjustment($this->getDiscountAdjustment(1000));
        $orderItemUnit = new OrderItemUnit($orderItem);
        $orderItemUnit->addAdjustment($this->getDiscountAdjustment(74, AdjustmentInterface::ORDER_UNIT_PROMOTION_ADJUSTMENT));
        $orderItemUnit->addAdjustment($this->getDiscountAdjustment(1000));
        $orderItemUnit = new OrderItemUnit($orderItem);
        $orderItemUnit->addAdjustment($this->getDiscountAdjustment(74, AdjustmentInterface::ORDER_UNIT_PROMOTION_ADJUSTMENT));
        $orderItemUnit->addAdjustment($this->getDiscountAdjustment(1000));
        $orderItemUnit = new OrderItemUnit($orderItem);
        $orderItemUnit->addAdjustment($this->getDiscountAdjustment(74, AdjustmentInterface::ORDER_UNIT_PROMOTION_ADJUSTMENT));
        $orderItemUnit->addAdjustment($this->getDiscountAdjustment(1000));
        $order->addItem($orderItem);

        self::assertSame(38504, $order->getTotal());

        $pausePayOrder = $this->mapper->mapFromSyliusPayment($this->getPayment($order), 'https://ok', 'https://ko');

        self::assertSame(385.04, $pausePayOrder->getAmount());

        $items = $pausePayOrder->getPurchasedItems();

        self::assertSame($items[0]->getName(), 'Star Wars Mug');
        self::assertSame($items[0]->getQuantity(), 5);
        self::assertSame($items[0]->getAmount(), 77.01); // this should be 77.008 but it is rounded to 77.01
    }

    private function getPayment(OrderInterface $order): PaymentInterface
    {
        $paymentMethod = new PaymentMethod();
        $paymentMethod->setCode('pausepay');
        $paymentMethod->setGatewayConfig(new GatewayConfig());

        $payment = new Payment();
        $payment->setMethod($paymentMethod);
        $payment->setCurrencyCode('EUR');
        $payment->setOrder($order);
        $payment->setAmount($order->getTotal());

        return $payment;
    }

    private function getBaseOrder(): OrderInterface
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

    private function getOrderItemWithUnit(
        string $name,
        int $unitPrice,
        int $quantity,
        ?int $itemUnitAdjustmentUnitAmount = null,
        ?int $orderAdjustmentUnitAmount = null
    ): OrderItem {
        $orderItem = new OrderItem();
        $orderItem->setUnitPrice($unitPrice);
        $orderItem->setVariantName($name);
        for ($i = 0; $i < $quantity; ++$i) {
            $orderItemUnit = new OrderItemUnit($orderItem);
            if ($itemUnitAdjustmentUnitAmount !== null) {
                $orderItemUnit->addAdjustment(
                    $this->getDiscountAdjustment(
                        $itemUnitAdjustmentUnitAmount,
                        AdjustmentInterface::ORDER_UNIT_PROMOTION_ADJUSTMENT
                    )
                );
            }
            if ($orderAdjustmentUnitAmount !== null) {
                $orderItemUnit->addAdjustment($this->getDiscountAdjustment($orderAdjustmentUnitAmount));
            }
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

    private function getDiscountAdjustment(int $amount, string $type = AdjustmentInterface::ORDER_PROMOTION_ADJUSTMENT): AdjustmentInterface
    {
        $adjustment = new Adjustment();
        $adjustment->setType($type);
        $adjustment->setAmount(-$amount);
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

    private function addShippingCost(Order $order): void
    {
        $shippingCost = 1230;
        $order->addAdjustment($this->getShippingAdjustment($shippingCost));
        $order->addAdjustment($this->getTaxAdjustment((int) (round($shippingCost * 0.22, 2))));
    }
}
