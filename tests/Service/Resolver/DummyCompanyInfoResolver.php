<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusPausePayPlugin\Service\Resolver;

use Sylius\Component\Core\Model\OrderInterface;
use Webgriffe\SyliusPausePayPlugin\Resolver\CompanyInfoResolverInterface;
use Webgriffe\SyliusPausePayPlugin\ValueObject\CompanyInfo;

final class DummyCompanyInfoResolver implements CompanyInfoResolverInterface
{
    public function resolveFromOrder(OrderInterface $order): CompanyInfo
    {
        return new CompanyInfo('Webgriffe SRL', '02277170359', 'support@webgriffe.com', 'pec@webgriffe.com');
    }
}
