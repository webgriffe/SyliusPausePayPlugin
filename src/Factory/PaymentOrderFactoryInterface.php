<?php

declare(strict_types=1);

namespace Webgriffe\SyliusPausePayPlugin\Factory;

use Webgriffe\SyliusPausePayPlugin\Entity\PaymentOrderInterface;

interface PaymentOrderFactoryInterface
{
    public function createNew(): PaymentOrderInterface;
}
