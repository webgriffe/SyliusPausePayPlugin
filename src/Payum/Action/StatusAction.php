<?php

declare(strict_types=1);

namespace Webgriffe\SyliusPausePayPlugin\Payum\Action;

use Payum\Core\Action\ActionInterface;
use Payum\Core\Exception\RequestNotSupportedException;
use Psr\Log\LoggerInterface;
use Sylius\Bundle\PayumBundle\Request\GetStatus;
use Sylius\Component\Core\Model\PaymentInterface as SyliusPaymentInterface;
use Webgriffe\SyliusPausePayPlugin\Client\PaymentState;
use Webgriffe\SyliusPausePayPlugin\Helper\PaymentDetailsHelper;
use Webgriffe\SyliusPausePayPlugin\Logger\LoggingHelperTrait;
use Webmozart\Assert\Assert;

/**
 * @psalm-type PaymentDetails array{uuid: string, redirect_url: string, created_at: string, status?: string}
 */
final class StatusAction implements ActionInterface
{
    use LoggingHelperTrait;

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
            $this->logInfo(
                $payment,
                'Request new or unknown.',
                ['isNew' => $request->isNew(), 'isUnknown' => $request->isUnknown()],
            );

            return;
        }

        if (!PaymentDetailsHelper::areValid($paymentDetails)) {
            $this->logInfo(
                $payment,
                'Payment details not valid. Payment marked as failed',
                ['Payment details' => $paymentDetails],
            );
            $request->markFailed();

            return;
        }

        /** @psalm-suppress InvalidArgument */
        $paymentStatus = PaymentDetailsHelper::getPaymentStatus($paymentDetails);
        if (in_array($paymentStatus, [PaymentState::CANCELLED, PaymentState::PENDING], true)) {
            $this->logInfo($payment, 'Payment cancelled or pending. Payment marked as canceled.');
            $request->markCanceled();

            return;
        }

        if (in_array($paymentStatus, [PaymentState::SUCCESS, PaymentState::AWAITING_CONFIRMATION], true)) {
            $this->logInfo($payment, 'Payment successfully or awaiting confirmation. Payment marked as captured.');
            $request->markCaptured();

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
}
