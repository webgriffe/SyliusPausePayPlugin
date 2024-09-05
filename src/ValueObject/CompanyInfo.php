<?php

declare(strict_types=1);

namespace Webgriffe\SyliusPausePayPlugin\ValueObject;

final class CompanyInfo
{
    public function __construct(
        private string $name,
        private string $vatNumber,
        private string $email,
        private string $pec,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getVatNumber(): string
    {
        return $this->vatNumber;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPec(): string
    {
        return $this->pec;
    }
}
