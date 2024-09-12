<?php

declare(strict_types=1);

namespace Webgriffe\SyliusPausePayPlugin\Repository;

use Webgriffe\SyliusPausePayPlugin\Entity\PaymentOrderInterface;

interface PaymentOrderRepositoryInterface
{
    public function add(PaymentOrderInterface $paymentOrder): void;

    public function findOneByPausePayOrderId(string $pausePayOrderId): ?PaymentOrderInterface;

    public function remove(PaymentOrderInterface $paymentOrder): void;
}
