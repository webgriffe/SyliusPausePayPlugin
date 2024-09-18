<?php

declare(strict_types=1);

namespace Webgriffe\SyliusPausePayPlugin\Checker;

use Sylius\Component\Core\Model\PaymentMethodInterface;
use Sylius\Component\Payment\Model\PaymentInterface as BasePaymentInterface;

interface PaymentMethodAvailabilityCheckerInterface
{
    public function isAvailable(BasePaymentInterface $subject, PaymentMethodInterface $paymentMethod): bool;
}
