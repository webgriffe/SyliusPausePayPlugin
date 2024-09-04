<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusPausePayPlugin\Unit\Mapper;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Sylius\Bundle\PayumBundle\Model\GatewayConfig;
use Sylius\Component\Core\Model\Order;
use Sylius\Component\Core\Model\Payment;
use Sylius\Component\Core\Model\PaymentMethod;
use Sylius\Component\Core\OrderPaymentStates;
use Webgriffe\SyliusPausePayPlugin\Mapper\OrderMapper;
use Webgriffe\SyliusPausePayPlugin\Mapper\OrderMapperInterface;

final class OrderMapperTest extends TestCase
{
    private OrderMapperInterface $mapper;

    protected function setUp(): void
    {
        $this->mapper = new OrderMapper();
    }

    public function test_it_maps_sylius_payment_to_pausepay_order(): void
    {

        $payment = new Payment();
        $payment->setAmount(1000);
        $payment->setCurrencyCode('EUR');

        $paymentMethod = new PaymentMethod();
        $paymentMethod->setCode('pausepay');
        $paymentMethod->setGatewayConfig(new GatewayConfig());
        $payment->setMethod($paymentMethod);

        $order = new Order();
        $order->setNumber('000001732');
        $order->setPaymentState(OrderPaymentStates::STATE_AWAITING_PAYMENT);
        $order->setCheckoutCompletedAt(new DateTimeImmutable('2022-09-10 10:00:10'));
        $payment->setOrder($order);

        $order = $this->mapper->mapFromSyliusPayment($payment);

        self::assertSame(1000, $order->getAmount());
    }
}
