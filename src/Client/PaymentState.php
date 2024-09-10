<?php

declare(strict_types=1);

namespace Webgriffe\SyliusPausePayPlugin\Client;

/**
 * @TODO Convert to Enum when PHP 8.1 will be dropped
 */
final class PaymentState
{
    public const SUCCESS = 'success';

    public const PENDING = 'pending';

    public const AWAITING_CONFIRMATION = 'awaiting_confirmation';

    public const CANCELLED = 'cancelled';

    public static function cases(): array
    {
        return [
            self::SUCCESS,
            self::PENDING,
            self::AWAITING_CONFIRMATION,
            self::CANCELLED,
        ];
    }
}
