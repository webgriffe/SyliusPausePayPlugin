<?php

declare(strict_types=1);

namespace Webgriffe\SyliusPausePayPlugin\Client\ValueObject\Response;

use DateTimeImmutable;
use Webgriffe\SyliusPausePayPlugin\Client\ValueObject\ArrayableInterface;

final class CreateOrderResult implements ArrayableInterface
{
    public function __construct(
        private string $redirectUrl,
        private string $uuid,
        private DateTimeImmutable $createdAt,
    ) {
    }

    public function getRedirectUrl(): string
    {
        return $this->redirectUrl;
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function toArray(): array
    {
        return [
            'redirectUrl' => $this->redirectUrl,
            'uuid' => $this->uuid,
            'createdAt' => $this->createdAt->format(DateTimeImmutable::ATOM),
        ];
    }
}
