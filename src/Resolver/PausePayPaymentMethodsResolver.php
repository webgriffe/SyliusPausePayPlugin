<?php

declare(strict_types=1);

namespace Webgriffe\SyliusPausePayPlugin\Resolver;

use Sylius\Component\Core\Model\AddressInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Core\Model\PaymentMethodInterface;
use Sylius\Component\Core\Repository\PaymentMethodRepositoryInterface;
use Sylius\Component\Payment\Model\PaymentInterface as BasePaymentInterface;
use Sylius\Component\Payment\Resolver\PaymentMethodsResolverInterface;
use Webgriffe\SyliusPausePayPlugin\Checker\PaymentMethodAvailabilityCheckerInterface;
use Webmozart\Assert\Assert;

final class PausePayPaymentMethodsResolver implements PaymentMethodsResolverInterface
{
    public function __construct(
        private PaymentMethodRepositoryInterface $paymentMethodRepository,
        private PaymentMethodAvailabilityCheckerInterface $paymentMethodAvailabilityChecker,
    ) {
    }

    /**
     * @param BasePaymentInterface|PaymentInterface $subject
     *
     * @return PaymentMethodInterface[]
     */
    public function getSupportedMethods(BasePaymentInterface $subject): array
    {
        Assert::true($this->supports($subject), 'This payment method is not support by resolver');
        Assert::isInstanceOf($subject, PaymentInterface::class);
        $order = $subject->getOrder();
        Assert::isInstanceOf($order, OrderInterface::class);
        $channel = $order->getChannel();
        Assert::isInstanceOf($channel, ChannelInterface::class);

        /** @var PaymentMethodInterface[] $paymentMethods */
        $paymentMethods = $this->paymentMethodRepository->findEnabledForChannel($channel);

        return array_filter(
            $paymentMethods,
            function (PaymentMethodInterface $paymentMethod) use ($subject) {
                $gatewayConfig = $paymentMethod->getGatewayConfig();
                if ($gatewayConfig === null) {
                    return false;
                }

                return $this->paymentMethodAvailabilityChecker->isAvailable($subject, $paymentMethod);
            },
        );
    }

    public function supports(BasePaymentInterface $subject): bool
    {
        if (!$subject instanceof PaymentInterface) {
            return false;
        }
        $order = $subject->getOrder();
        if (!$order instanceof OrderInterface) {
            return false;
        }
        $channel = $order->getChannel();
        if (!$channel instanceof ChannelInterface) {
            return false;
        }
        $paymentMethod = $subject->getMethod();
        if (!$paymentMethod instanceof PaymentMethodInterface) {
            return false;
        }
        $billingAddress = $order->getBillingAddress();
        if (!$billingAddress instanceof AddressInterface) {
            return false;
        }
        $currencyCode = $order->getCurrencyCode();

        return $currencyCode !== null;
    }
}
