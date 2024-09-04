<?php

declare(strict_types=1);

namespace Webgriffe\SyliusPausePayPlugin\Mapper;

use Sylius\Component\Core\Model\PaymentInterface;
use Webgriffe\SyliusPausePayPlugin\Client\ValueObject\Order;

interface OrderMapperInterface
{
    public function mapFromSyliusPayment(PaymentInterface $payment, string $captureUrl, string $cancelUrl): Order;
}
