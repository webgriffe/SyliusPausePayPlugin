<?php

declare(strict_types=1);

namespace Webgriffe\SyliusPausePayPlugin\Repository;

use Webgriffe\SyliusPausePayPlugin\Entity\PaymentOrderInterface;

interface PaymentOrderRepositoryInterface
{
    public function findOneByPausePayOrderId(string $pausePayOrderId): ?PaymentOrderInterface;
}
