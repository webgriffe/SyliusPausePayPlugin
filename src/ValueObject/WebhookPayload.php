<?php

declare(strict_types=1);

namespace Webgriffe\SyliusPausePayPlugin\ValueObject;

use InvalidArgumentException;

final class WebhookPayload
{
    public const EVENT_TYPE_OK = 'order.ok';

    public const EVENT_TYPE_KO = 'order.ko';

    private bool $isSuccessful = false;

    public function __construct(
        private string $eventType,
        private string $eventID,
        private string $orderID,
        private \DateTime $createdAt,
    ) {
        if ($this->eventType === self::EVENT_TYPE_OK) {
            $this->isSuccessful = true;
        } elseif ($this->eventType === self::EVENT_TYPE_KO) {
            $this->isSuccessful = false;
        } else {
            throw new InvalidArgumentException(
                sprintf(
                    'Invalid event type, expected one of: %s, %s',
                    self::EVENT_TYPE_OK,
                    self::EVENT_TYPE_KO,
                ),
            );
        }
    }

    public function getEventType(): string
    {
        return $this->eventType;
    }

    public function isSuccessful(): bool
    {
        return $this->isSuccessful;
    }

    public function getEventID(): string
    {
        return $this->eventID;
    }

    public function getOrderID(): string
    {
        return $this->orderID;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }
}
