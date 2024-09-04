<?php

declare(strict_types=1);

namespace Webgriffe\SyliusPausePayPlugin\Resolver;

use RuntimeException;
use Sylius\Component\Core\Model\OrderInterface;
use Webgriffe\SyliusPausePayPlugin\ValueObject\CompanyInfo;

final class CompanyInfoResolver implements CompanyInfoResolverInterface
{
    public function resolveFromOrder(OrderInterface $order): CompanyInfo
    {
        throw new RuntimeException('You must replace this service with your own implementation.');
    }
}
