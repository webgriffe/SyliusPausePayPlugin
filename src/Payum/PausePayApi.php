<?php

declare(strict_types=1);

namespace Webgriffe\SyliusPausePayPlugin\Payum;

final class PausePayApi
{
    public const GATEWAY_CODE = 'pausepay';

    /**
     * @param array{sandbox: bool, merchant_key: string, allowed_terms: array<array-key, int>} $config
     */
    public function __construct(private array $config)
    {
    }

    public function getMerchantKey(): string
    {
        return $this->config['merchant_key'];
    }

    public function isSandBox(): bool
    {
        return $this->config['sandbox'];
    }

    /**
     * @return array<array-key, int>
     */
    public function getAllowedTerms(): array
    {
        return array_values($this->config['allowed_terms']);
    }
}
