<?php

declare(strict_types=1);

namespace Webgriffe\SyliusPausePayPlugin\Client\ValueObject\Response;

use DateTimeImmutable;

final class CreateOrderResult
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
}
