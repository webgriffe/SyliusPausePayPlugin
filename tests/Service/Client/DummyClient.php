<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusPausePayPlugin\Service\Client;

use Webgriffe\SyliusPausePayPlugin\Client\ClientInterface;
use Webgriffe\SyliusPausePayPlugin\Client\ValueObject\Order;
use Webgriffe\SyliusPausePayPlugin\Client\ValueObject\Response\CreateOrderResult;

final class DummyClient implements ClientInterface
{
    public function createOrder(Order $order): CreateOrderResult
    {
        return new CreateOrderResult('https://example.com', '123456', new \DateTimeImmutable());
    }
}
