<?php

declare(strict_types=1);

namespace Webgriffe\SyliusPausePayPlugin\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class PausePayPaymentMethodUnique extends Constraint
{
    public string $message = 'webgriffe_sylius_pausepay.payment_method.unique';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
