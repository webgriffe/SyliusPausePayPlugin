<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusPausePayPlugin\App\Resolver;

use Sylius\Component\Core\Model\OrderInterface;
use Webgriffe\SyliusPausePayPlugin\Resolver\CompanyInfoResolverInterface;
use Webgriffe\SyliusPausePayPlugin\ValueObject\CompanyInfo;

final class CompanyInfoResolver implements CompanyInfoResolverInterface
{
    public function resolveFromOrder(OrderInterface $order): CompanyInfo
    {
        return new CompanyInfo(
            'Azienda A',
            'IT73228252614',
            'whatever@example.com'
        );
    }
}
