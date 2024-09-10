<?php

declare(strict_types=1);

namespace Webgriffe\SyliusPausePayPlugin\Payum\Action;

use Doctrine\Persistence\ObjectManager;
use Payum\Core\Action\ActionInterface;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Request\Cancel;
use Psr\Log\LoggerInterface;
use SM\Factory\FactoryInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\PaymentInterface as SyliusPaymentInterface;
use Sylius\Component\Order\Processor\OrderProcessorInterface;
use Sylius\Component\Payment\PaymentTransitions;
use Webgriffe\SyliusPausePayPlugin\Client\PaymentState;
use Webgriffe\SyliusPausePayPlugin\Helper\PaymentDetailsHelper;
use Webmozart\Assert\Assert;

/**
 * @psalm-type PaymentDetails array{uuid: string, redirect_url: string, created_at: string, status?: string}
 */
final class CancelAction implements ActionInterface
{
    public function __construct(
        private LoggerInterface $logger,
        private ObjectManager $objectManager,
        private FactoryInterface $stateMachineFactory,
        private OrderProcessorInterface $orderPaymentProcessor,
    ) {
    }

    /**
     * @param Cancel|mixed $request
     */
    public function execute($request): void
    {
        RequestNotSupportedException::assertSupports($this, $request);
        Assert::isInstanceOf($request, Cancel::class);

        $payment = $request->getModel();
        Assert::isInstanceOf($payment, SyliusPaymentInterface::class);

        $order = $payment->getOrder();
        Assert::isInstanceOf($order, OrderInterface::class);

        $this->logInfo($payment, 'Start cancel action');

        $paymentStateMachine = $this->stateMachineFactory->get($payment, PaymentTransitions::GRAPH);
        if (!$paymentStateMachine->can(PaymentTransitions::TRANSITION_CANCEL)) {
            $this->logInfo($payment, 'Payment cannot be cancelled.');

            return;
        }

        /** @var PaymentDetails $paymentDetails */
        $paymentDetails = $payment->getDetails();
        PaymentDetailsHelper::assertPaymentDetailsAreValid($paymentDetails);

        $paymentStateMachine->apply(PaymentTransitions::TRANSITION_CANCEL);
        $this->logInfo($payment, 'Cancelled payment. Start processing order.');

        /** @var SyliusPaymentInterface $lastPayment */
        $lastPayment = $order->getLastPayment();
        if ($lastPayment->getState() === SyliusPaymentInterface::STATE_NEW) {
            $this->objectManager->flush();
            $this->logInfo($payment, 'Order flushed.');

            return;
        }

        $this->orderPaymentProcessor->process($order);
        $this->objectManager->flush();

        $paymentDetails = PaymentDetailsHelper::addPaymentStatus(
            $paymentDetails,
            PaymentState::CANCELLED,
        );
        $payment->setDetails($paymentDetails);

        $this->logInfo($payment, 'Order processed and flushed.');
    }

    public function supports($request): bool
    {
        return $request instanceof Cancel && $request->getModel() instanceof SyliusPaymentInterface;
    }

    private function logInfo(SyliusPaymentInterface $payment, string $message, array $context = []): void
    {
        $this->logger->info(sprintf('[Payment #%s]: %s.', (string) $payment->getId(), $message, ), $context);
    }
}
