<?php

declare(strict_types=1);

namespace Webgriffe\SyliusPausePayPlugin\Client\ValueObject;

final class Order
{
    // todo
    public function __construct(
        private int $amount,
        private string $number,
    ) {
    }

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function getNumber(): string
    {
        return $this->number;
    }

    /**
     * @return array<string, mixed> The array representation of the contract to send to Pagolight API
     */
    public function toArrayParams(): array
    {
        // todo
        return [
            'amount' => $this->amount,
            'number' => $this->number,
        ];
    }
}
