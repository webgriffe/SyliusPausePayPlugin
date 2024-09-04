<?php

declare(strict_types=1);

namespace Webgriffe\SyliusPausePayPlugin\Client\ValueObject;

interface ArrayableInterface
{
    /**
     * @return array<string, mixed> The array representation of the entity to send to PausePay
     */
    public function toArray(): array;
}
