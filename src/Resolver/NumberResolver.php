<?php

declare(strict_types=1);

namespace Webgriffe\SyliusPausePayPlugin\Resolver;

use Sylius\Component\Core\Model\OrderInterface;
use Webgriffe\SyliusPausePayPlugin\Provider\ConfigurationProviderInterface;
use Webmozart\Assert\Assert;

final class NumberResolver implements NumberResolverInterface
{
    private const NOT_ELIGIBLE = 'NE'; // Buyer not eligible

    private const ELIGIBLE_NO_INSURANCE = 'NC'; // Buyer eligible, without insurance coverage;

    private const ELIGIBLILITY_NOT_AVAILABLE = 'NR'; // Eligibility not yet available;

    private const ELIGIBLE_WITH_INSURANCE = 'YC'; // Buyer eligible, with insurance coverage.

    public function __construct(private ConfigurationProviderInterface $configurationProvider)
    {
    }

    public function resolveFromOrder(OrderInterface $order): string
    {
        /** @var int|null $id */
        $id = $order->getId();
        Assert::notNull($id);

        if (!$this->configurationProvider->isSandbox()) {
            return (string) $id;
        }

        $number = sprintf('%s-%s', $this->computeTestPrefix($order), $id);

        return $this->addTaxIdToNumber($number, $order);
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

    private function addTaxIdToNumber(string $number, OrderInterface $order): string
    {
        $channel = $order->getChannel();
        $billingData = $channel?->getShopBillingData();
        $taxId = $billingData?->getTaxId();
        if (!is_string($taxId)) {
            return $number;
        }

        $taxId = trim($taxId);
        if ($taxId === '') {
            return $number;
        }

        return sprintf('%s#%s', $number, $taxId);
    }
}
