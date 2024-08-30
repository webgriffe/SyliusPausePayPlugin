<?php

declare(strict_types=1);

namespace Webgriffe\SyliusPausePayPlugin\Payum\Request\Api;

use Webgriffe\SyliusPausePayPlugin\Client\ValueObject\Order;
use Webgriffe\SyliusPausePayPlugin\Client\ValueObject\Response\CreateOrderResult;

final class CreateOrder
{
    private ?CreateOrderResult $result = null;

    public function __construct(private Order $order)
    {
    }

    public function getOrder(): Order
    {
        return $this->order;
    }

    public function getResult(): ?CreateOrderResult
    {
        return $this->result;
    }

    public function setResult(?CreateOrderResult $result): void
    {
        $this->result = $result;
    }
}
