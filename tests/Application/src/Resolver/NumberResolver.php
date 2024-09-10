<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusPausePayPlugin\App\Resolver;

use Sylius\Component\Core\Model\OrderInterface;
use Webgriffe\SyliusPausePayPlugin\Mapper\NumberResolverInterface;
use Webmozart\Assert\Assert;

final class NumberResolver implements NumberResolverInterface
{
    private const NOT_ELIGIBLE = 'NE'; // Buyer not eligible
    private const ELIGIBLE_NO_INSURANCE = 'NC'; // Buyer eligible, without insurance coverage;
    private const ELIGIBLILITY_NOT_AVAILABLE = 'NR'; // Eligibility not yet available;
    private const ELIGIBLE_WITH_INSURANCE = 'YC'; // Buyer eligible, with insurance coverage.

    public function resolveFromOrder(OrderInterface $order): string
    {
        $number = $order->getNumber();
        Assert::stringNotEmpty($number);
        // todo: custom webhooks with "#VAT_NUMBER" -> see guida-seller.pdf

        return sprintf('%s-%s', $this->computeTestPrefix($order), $number);
    }

    private function computeTestPrefix(OrderInterface $order): string
    {
        $notes = $order->getNotes();
        if ($notes === null) {
            return self::ELIGIBLE_WITH_INSURANCE;
        }

        $notes = strtoupper($notes);
        if (str_starts_with($notes, self::NOT_ELIGIBLE)) {
            return self::NOT_ELIGIBLE;
        }
        if (str_starts_with($notes, self::ELIGIBLE_NO_INSURANCE)) {
            return self::ELIGIBLE_NO_INSURANCE;
        }
        if (str_starts_with($notes, self::ELIGIBLILITY_NOT_AVAILABLE)) {
            return self::ELIGIBLILITY_NOT_AVAILABLE;
        }

        return self::ELIGIBLE_WITH_INSURANCE;
    }
}
