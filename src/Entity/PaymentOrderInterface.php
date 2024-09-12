<?php

declare(strict_types=1);

namespace Webgriffe\SyliusPausePayPlugin\Entity;

use Sylius\Bundle\PayumBundle\Model\PaymentSecurityTokenInterface;

interface PaymentOrderInterface
{
    public function getPaymentToken(): PaymentSecurityTokenInterface;

    public function setPaymentToken(PaymentSecurityTokenInterface $paymentToken): void;

    public function getOrderId(): string;

    public function setOrderId(string $orderId): void;
}
