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
            'Azienda B',
            'IT82500048660',
            'martino.bianchi1269592030633719343@fly.sbx',
            'martino.bianchi1269592030633719343@fly.sbx',
        );
    }
}
