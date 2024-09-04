<?php

declare(strict_types=1);

namespace Webgriffe\SyliusPausePayPlugin\Client\ValueObject;

final class OrderItem implements ArrayableInterface
{
    public function __construct(
        private string $name,
        private int $quantity,
        private float $amount, // unit price of the product (VAT included)
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function toArray(): array
    {
        return [
            'description' => $this->name,
            'quantity' => $this->quantity,
            'amount' => $this->amount,
        ];
    }
}
