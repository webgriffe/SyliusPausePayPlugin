<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusPausePayPlugin\Unit\Resolver;

use PHPUnit\Framework\TestCase;
use Sylius\Bundle\PayumBundle\Model\GatewayConfig;
use Sylius\Component\Core\Model\Address;
use Sylius\Component\Core\Model\Channel;
use Sylius\Component\Core\Model\Order;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\OrderItem;
use Sylius\Component\Core\Model\OrderItemUnit;
use Sylius\Component\Core\Model\Payment;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Core\Model\PaymentMethod;
use Sylius\Component\Core\Model\PaymentMethodInterface;
use Sylius\Component\Core\Repository\PaymentMethodRepositoryInterface;
use Sylius\Component\Payment\Resolver\PaymentMethodsResolverInterface;
use Webgriffe\SyliusPausePayPlugin\Payum\PausePayApi;
use Webgriffe\SyliusPausePayPlugin\Resolver\PausePayPaymentMethodsResolver;

final class PausePayPaymentMethodsResolverTest extends TestCase
{
    private PaymentMethodsResolverInterface $resolver;

    protected function setUp(): void
    {
        $repoMock = $this->createMock(PaymentMethodRepositoryInterface::class);
        $repoMock
            ->method('findEnabledForChannel')
            ->willReturn(
                [
                    $this->getPaymentMethod('paypal'),
                    $this->getPaymentMethod('credit_card'),
                    $this->getPaymentMethod(PausePayApi::GATEWAY_CODE),
                ]
            );
        $this->resolver = new PausePayPaymentMethodsResolver($repoMock);
    }

    public function test_it_supports_payment(): void
    {
        self::assertTrue($this->resolver->supports($this->getPayment($this->getBaseOrder())));
    }

    public function test_it_resolves_payment_methods_by_returning_pausepay_and_all_others(): void
    {
        $supportedMethods = $this->resolver->getSupportedMethods($this->getPayment($this->getBaseOrder()));

        self::assertCount(3, $supportedMethods);

        self::assertSame($supportedMethods[0]->getCode(), 'paypal');
        self::assertSame($supportedMethods[1]->getCode(), 'credit_card');
        self::assertSame($supportedMethods[2]->getCode(), PausePayApi::GATEWAY_CODE);
    }

    public function test_it_does_not_resolve_pausepay_with_non_italian_billing_address(): void
    {
        $order = $this->getBaseOrder();
        $order->getBillingAddress()->setCountryCode('FR');
        $supportedMethods = $this->resolver->getSupportedMethods($this->getPayment($order));

        self::assertCount(2, $supportedMethods);

        self::assertSame($supportedMethods[0]->getCode(), 'paypal');
        self::assertSame($supportedMethods[1]->getCode(), 'credit_card');
    }

    public function test_it_does_not_resolve_pausepay_with_non_euro_currency(): void
    {
        $order = $this->getBaseOrder();
        $order->setCurrencyCode('USD');
        $supportedMethods = $this->resolver->getSupportedMethods($this->getPayment($order));

        self::assertCount(2, $supportedMethods);

        self::assertSame($supportedMethods[0]->getCode(), 'paypal');
        self::assertSame($supportedMethods[1]->getCode(), 'credit_card');
    }

    public function test_it_does_not_resolve_pausepay_with_amount_below_500(): void
    {
        $order = $this->getBaseOrder();
        $order->removeItem($order->getItems()->first());
        $supportedMethods = $this->resolver->getSupportedMethods($this->getPayment($order));

        self::assertCount(2, $supportedMethods);

        self::assertSame($supportedMethods[0]->getCode(), 'paypal');
        self::assertSame($supportedMethods[1]->getCode(), 'credit_card');
    }

    public function test_it_does_not_resolve_pausepay_with_amount_above_20000(): void
    {
        $order = $this->getBaseOrder();
        $order->addItem($this->getOrderItemWithUnit('Star Wars Mug', 10000000, 1));
        $supportedMethods = $this->resolver->getSupportedMethods($this->getPayment($order));

        self::assertCount(2, $supportedMethods);

        self::assertSame($supportedMethods[0]->getCode(), 'paypal');
        self::assertSame($supportedMethods[1]->getCode(), 'credit_card');
    }

    private function getPayment(OrderInterface $order): PaymentInterface
    {
        $payment = new Payment();
        $payment->setMethod($this->getPaymentMethod(PausePayApi::GATEWAY_CODE));
        $payment->setCurrencyCode('EUR');
        $payment->setOrder($order);
        $payment->setAmount($order->getTotal());

        return $payment;
    }

    private function getBaseOrder(): OrderInterface
    {
        $order = new Order();
        $order->setChannel(new Channel());
        $billingAddress = new Address();
        $billingAddress->setCountryCode('IT');
        $order->setBillingAddress($billingAddress);
        $order->setCurrencyCode('EUR');
        $order->addItem($this->getOrderItemWithUnit('Star Wars Mug', 100000, 1));

        return $order;
    }

    private function getPaymentMethod(string $code): PaymentMethodInterface
    {
        $paypal = new PaymentMethod();
        $gateway = new GatewayConfig();
        $gateway->setFactoryName($code);
        $paypal->setGatewayConfig($gateway);
        $paypal->setCode($code);
        return $paypal;
    }

    private function getOrderItemWithUnit(
        string $name,
        int $unitPrice,
        int $quantity,
    ): OrderItem {
        $orderItem = new OrderItem();
        $orderItem->setUnitPrice($unitPrice);
        $orderItem->setVariantName($name);
        for ($i = 0; $i < $quantity; ++$i) {
            new OrderItemUnit($orderItem);
        }

        return $orderItem;
    }
}
