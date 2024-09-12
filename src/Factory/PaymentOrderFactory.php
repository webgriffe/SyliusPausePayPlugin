<?php

declare(strict_types=1);

namespace Webgriffe\SyliusPausePayPlugin\Factory;

use Webgriffe\SyliusPausePayPlugin\Entity\PaymentOrderInterface;
use Webmozart\Assert\Assert;

final class PaymentOrderFactory implements PaymentOrderFactoryInterface
{
    /**
     * @param class-string $paymentOrderClass
     */
    public function __construct(
        private string $paymentOrderClass,
    ) {
    }

    public function createNew(): PaymentOrderInterface
    {
        /** @psalm-suppress MixedMethodCall */
        $paymentOrder = new $this->paymentOrderClass();
        Assert::isInstanceOf($paymentOrder, PaymentOrderInterface::class);

        return $paymentOrder;
    }
}
