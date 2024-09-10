<?php

declare(strict_types=1);

namespace Webgriffe\SyliusPausePayPlugin\Mapper;

use Sylius\Component\Core\Model\OrderInterface;
use Webmozart\Assert\Assert;

final class NumberResolver implements NumberResolverInterface
{
    public function resolveFromOrder(OrderInterface $order): string
    {
        $number = $order->getNumber();
        Assert::stringNotEmpty($number);

        return $number;
    }
}
