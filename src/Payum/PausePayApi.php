<?php

declare(strict_types=1);

namespace Webgriffe\SyliusPausePayPlugin\Payum;

final class PausePayApi
{
    public const GATEWAY_CODE = 'pausepay';

    public const API_KEY_FIELD_NAME = 'api_key';

    public const SANDBOX_FIELD_NAME = 'sandbox';

    /**
     * @param array{sandbox: bool, api_key: string} $config
     */
    public function __construct(private array $config)
    {
    }

    public function getApiKey(): string
    {
        return $this->config[self::API_KEY_FIELD_NAME];
    }

    public function isSandBox(): bool
    {
        return $this->config[self::SANDBOX_FIELD_NAME];
    }
}
