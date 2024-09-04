<?php

declare(strict_types=1);

namespace Webgriffe\SyliusPausePayPlugin\Resolver;

use Sylius\Component\Core\Model\OrderInterface;
use Webgriffe\SyliusPausePayPlugin\ValueObject\CompanyInfo;

interface CompanyInfoResolverInterface
{
    public function resolveFromOrder(OrderInterface $order): CompanyInfo;
}
