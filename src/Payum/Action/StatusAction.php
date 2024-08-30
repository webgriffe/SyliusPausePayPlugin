<?php

declare(strict_types=1);

namespace Webgriffe\SyliusPausePayPlugin\Payum\Action;

use Payum\Core\Action\ActionInterface;
use Payum\Core\Exception\RequestNotSupportedException;
use Psr\Log\LoggerInterface;
use Sylius\Bundle\PayumBundle\Request\GetStatus;
use Sylius\Component\Core\Model\PaymentInterface as SyliusPaymentInterface;
use Webmozart\Assert\Assert;

/**
 * @psalm-type PaymentDetails array{contract_uuid: string, redirect_url: string, created_at: string, status?: string}
 */
final class StatusAction implements ActionInterface
{
    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @param GetStatus|mixed $request
     */
    public function execute($request): void
    {
        RequestNotSupportedException::assertSupports($this, $request);
        Assert::isInstanceOf($request, GetStatus::class);

        /** @var SyliusPaymentInterface $payment */
        $payment = $request->getFirstModel();

        $this->logInfo($payment, 'Start status action');

        $paymentDetails = $payment->getDetails();

        if ($paymentDetails === []) {
            $this->logInfo($payment, 'Empty stored details.');
            $request->markNew();

            return;
        }

        if (!$request->isNew() && !$request->isUnknown()) {
            $this->logInfo($payment, 'Request new or unknown.', ['isNew' => $request->isNew(), 'isUnknown' => $request->isUnknown()]);

            return;
        }

        if (200 === $paymentDetails['status']) {
            $this->logInfo($payment, 'Payment successfully or awaiting confirmation. Payment marked as captured.');
            $request->markCaptured();

            return;
        }

        if (400 === $paymentDetails['status']) {
            $this->logInfo($payment, 'Payment cancelled or pending. Payment marked as canceled.');
            $request->markCanceled();

            return;
        }
    }

    public function supports($request): bool
    {
        return
            $request instanceof GetStatus &&
            $request->getFirstModel() instanceof SyliusPaymentInterface
        ;
    }

    private function logInfo(SyliusPaymentInterface $payment, string $message, array $context = []): void
    {
        $this->logger->info(sprintf('[Payment #%s]: %s.', (string) $payment->getId(), $message, ), $context);
    }
}
