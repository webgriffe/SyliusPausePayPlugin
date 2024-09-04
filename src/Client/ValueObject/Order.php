<?php

declare(strict_types=1);

namespace Webgriffe\SyliusPausePayPlugin\Client\ValueObject;

use DateTimeImmutable;

final class Order implements ArrayableInterface
{
    /**
     * @param OrderItem[] $purchasedItems
     */
    public function __construct(
        private float $amount, // the total amount of the cart, including tax, shipment etc.
        private string $number, // the unique number of the order
        private DateTimeImmutable $issueDate, // order creation date
        private string $description, // description of the purchased goods
        private string $remittance, // reason for the BUYER transfer
        private string $okRedirectUrl, // redirect URL in case of success
        private string $koRedirectUrl, // redirect URL in case of failure
        private string $buyerInfoName,
        private string $buyerInfoVatNumber,
        private string $buyerInfoEmail,
        private array $purchasedItems,
    ) {
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getNumber(): string
    {
        return $this->number;
    }

    public function getIssueDate(): DateTimeImmutable
    {
        return $this->issueDate;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getRemittance(): string
    {
        return $this->remittance;
    }

    public function getOkRedirectUrl(): string
    {
        return $this->okRedirectUrl;
    }

    public function getKoRedirectUrl(): string
    {
        return $this->koRedirectUrl;
    }

    public function getBuyerInfoName(): string
    {
        return $this->buyerInfoName;
    }

    public function getBuyerInfoVatNumber(): string
    {
        return $this->buyerInfoVatNumber;
    }

    public function getBuyerInfoEmail(): string
    {
        return $this->buyerInfoEmail;
    }

    /**
     * @return OrderItem[]
     */
    public function getPurchasedItems(): array
    {
        return $this->purchasedItems;
    }

    public function toArray(): array
    {
        $items = array_map(
            static fn (OrderItem $orderItem) => $orderItem->toArray(),
            $this->purchasedItems,
        );

        return [
            'amount' => $this->amount,
            'number' => $this->number,
            'issueDate' => $this->issueDate->format('d-m-Y H:i'), // today as date DD/MM/YYYY HH:mm
            'description' => $this->description,
            'remittance' => $this->remittance,
            'okRedirect' => $this->okRedirectUrl,
            'koRedirect' => $this->koRedirectUrl,
            'allowToEditRemittance' => false, // the reason for the transfer is editable by the BUYER
            'buyerInfo' => [
                'name' => $this->buyerInfoName,
                'vatCode' => $this->buyerInfoVatNumber,
                'email' => $this->buyerInfoEmail,
            ],
            'items' => $items,
            'allowSCTPayment' => true, // if PausePay is not available, allow to pay by classic instant Wire Bank?
        ];
    }
}
