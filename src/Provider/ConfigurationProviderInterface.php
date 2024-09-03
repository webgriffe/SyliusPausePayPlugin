<?php

declare(strict_types=1);

namespace Webgriffe\SyliusPausePayPlugin\Provider;

interface ConfigurationProviderInterface
{
    public function getApiKey(): string;

    public function isSandbox(): bool;
}
