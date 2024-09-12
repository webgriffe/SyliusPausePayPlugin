<?php

declare(strict_types=1);

namespace Webgriffe\SyliusPausePayPlugin\Entity;

use Sylius\Bundle\PayumBundle\Model\PaymentSecurityTokenInterface;
use Sylius\Component\Resource\Model\TimestampableTrait;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
class PaymentOrder implements PaymentOrderInterface
{
    use TimestampableTrait;

    protected int $id;

    protected PaymentSecurityTokenInterface $paymentToken;

    protected string $orderId;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getPaymentToken(): PaymentSecurityTokenInterface
    {
        return $this->paymentToken;
    }

    public function setPaymentToken(PaymentSecurityTokenInterface $paymentToken): void
    {
        $this->paymentToken = $paymentToken;
    }

    public function getOrderId(): string
    {
        return $this->orderId;
    }

    public function setOrderId(string $orderId): void
    {
        $this->orderId = $orderId;
    }
}
