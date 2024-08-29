<?php

declare(strict_types=1);

namespace spec\Webgriffe\SyliusPausePayPlugin\Resolver;

use PhpSpec\ObjectBehavior;
use Sylius\Bundle\PayumBundle\Model\GatewayConfigInterface;
use Sylius\Component\Core\Model\AddressInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Core\Model\PaymentMethodInterface;
use Sylius\Component\Core\Repository\PaymentMethodRepositoryInterface;
use Sylius\Component\Payment\Resolver\PaymentMethodsResolverInterface;
use Webgriffe\SyliusPausePayPlugin\Payum\PausePayApi;
use Webgriffe\SyliusPausePayPlugin\Resolver\PausePayPaymentMethodsResolver;

final class PausePayPaymentMethodsResolverSpec extends ObjectBehavior
{
    public function let(
        PaymentMethodRepositoryInterface $paymentMethodRepository,
        PaymentInterface $payment,
        PaymentMethodInterface $pausepayPaymentMethod,
        PaymentMethodInterface $otherPaymentMethod,
        OrderInterface $order,
        ChannelInterface $channel,
        AddressInterface $billingAddress,
        AddressInterface $shippingAddress,
        GatewayConfigInterface $pausepayGatewayConfig,
        GatewayConfigInterface $otherGatewayConfig,
    ): void {
        $billingAddress->getCountryCode()->willReturn('IT');
        $shippingAddress->getCountryCode()->willReturn('IT');

        $order->getChannel()->willReturn($channel);
        $order->getBillingAddress()->willReturn($billingAddress);
        $order->getShippingAddress()->willReturn($shippingAddress);
        $order->getCurrencyCode()->willReturn('EUR');
        $order->getTotal()->willReturn(19000);

        $payment->getOrder()->willReturn($order);
        $payment->getMethod()->willReturn($pausepayPaymentMethod);

        $pausepayGatewayConfig->getFactoryName()->willReturn(PausePayApi::GATEWAY_CODE);
        $otherGatewayConfig->getFactoryName()->willReturn('other');

        $pausepayPaymentMethod->getCode()->willReturn('PAUSEPAY_PAYMENT_METHOD_CODE');
        $pausepayPaymentMethod->getGatewayConfig()->willReturn($pausepayGatewayConfig);
        $otherPaymentMethod->getCode()->willReturn('other_payment_method');
        $otherPaymentMethod->getGatewayConfig()->willReturn($otherGatewayConfig);

        $paymentMethodRepository->findEnabledForChannel($channel)->willReturn([
            $pausepayPaymentMethod,
            $otherPaymentMethod,
        ]);

        $this->beConstructedWith($paymentMethodRepository);
    }

    public function it_is_initializable(): void
    {
        $this->shouldHaveType(PausePayPaymentMethodsResolver::class);
    }

    public function it_implements_payment_methods_resolver_interface(): void
    {
        $this->shouldImplement(PaymentMethodsResolverInterface::class);
    }

    public function it_resolves_pausepay_payment_methods_if_eligible(
        PaymentInterface $payment,
        PaymentMethodInterface $pausepayPaymentMethod,
        PaymentMethodInterface $otherPaymentMethod,
    ): void {
        $this->getSupportedMethods($payment)->shouldReturn([
            0 => $pausepayPaymentMethod,
            2 => $otherPaymentMethod,
        ]);
    }

    public function it_does_not_resolve_pausepay_payment_method_if_order_amount_is_equal_or_under_50000(
        PaymentInterface $payment,
        PaymentMethodInterface $pausepayPaymentMethod,
        PaymentMethodInterface $otherPaymentMethod,
        OrderInterface $order,
    ): void {
        $order->getTotal()->willReturn(50000);
        $this->getSupportedMethods($payment)->shouldReturn([
            0 => $pausepayPaymentMethod,
            2 => $otherPaymentMethod,
        ]);

        $order->getTotal()->willReturn(9900);
        $this->getSupportedMethods($payment)->shouldReturn([
            2 => $otherPaymentMethod,
        ]);
    }

    public function it_does_not_resolve_pausepay_payment_methods_if_order_currency_is_not_supported(
        PaymentInterface $payment,
        PaymentMethodInterface $otherPaymentMethod,
        OrderInterface $order,
    ): void {
        $order->getCurrencyCode()->willReturn('USD');
        $this->getSupportedMethods($payment)->shouldReturn([
            2 => $otherPaymentMethod,
        ]);
    }

    public function it_does_not_resolve_pausepay_payment_methods_if_country_code_is_not_supported(
        PaymentInterface $payment,
        PaymentMethodInterface $otherPaymentMethod,
        AddressInterface $billingAddress,
        AddressInterface $shippingAddress,
    ): void {
        $billingAddress->getCountryCode()->willReturn('US');
        $this->getSupportedMethods($payment)->shouldReturn([
            2 => $otherPaymentMethod,
        ]);

        $shippingAddress->getCountryCode()->willReturn('US');
        $this->getSupportedMethods($payment)->shouldReturn([
            2 => $otherPaymentMethod,
        ]);
    }
}
