<?php

declare(strict_types=1);

namespace Webgriffe\SyliusPausePayPlugin\Logger;

use Sylius\Component\Core\Model\PaymentInterface as SyliusPaymentInterface;

trait LoggingHelperTrait
{
    private function logInfo(SyliusPaymentInterface $payment, string $message, array $context = []): void
    {
        $this->logger->info(sprintf('[Payment #%s]: %s.', (string) $payment->getId(), $message, ), $context);
    }

    private function logError(SyliusPaymentInterface $payment, string $message, array $context = []): void
    {
        $this->logger->error(sprintf('[Payment #%s]: %s.', (string) $payment->getId(), $message, ), $context);
    }
}
