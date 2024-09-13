<?php

declare(strict_types=1);

namespace Webgriffe\SyliusPausePayPlugin\Resolver;

use Sylius\Component\Core\Model\OrderInterface;

interface NumberResolverInterface
{
    public function resolveFromOrder(OrderInterface $order): string;
}
