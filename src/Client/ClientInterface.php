<?php

declare(strict_types=1);

namespace Webgriffe\SyliusPausePayPlugin\Client;

use Webgriffe\SyliusPausePayPlugin\Client\ValueObject\Order;
use Webgriffe\SyliusPausePayPlugin\Client\ValueObject\Response\CreateOrderResult;

interface ClientInterface
{
    public function createOrder(Order $order): CreateOrderResult;
}
