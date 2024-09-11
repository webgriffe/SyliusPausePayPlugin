<?php

declare(strict_types=1);

namespace Webgriffe\SyliusPausePayPlugin\Payum\Action;

use Payum\Core\Action\ActionInterface;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Reply\HttpRedirect;
use Payum\Core\Request\Cancel;
use Psr\Log\LoggerInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\PaymentInterface as SyliusPaymentInterface;
use Symfony\Component\Routing\RouterInterface;
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
        private RouterInterface $router,
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

        /** @var PaymentDetails $paymentDetails */
        $paymentDetails = $payment->getDetails();
        PaymentDetailsHelper::assertPaymentDetailsAreValid($paymentDetails);

        $this->logger->info('Redirecting the user to the Sylius Pagolight waiting page.');

        $order = $payment->getOrder();
        Assert::isInstanceOf($order, OrderInterface::class);

        $paymentDetails = PaymentDetailsHelper::addPaymentStatus(
            $paymentDetails,
            PaymentState::CANCELLED,
        );
        $payment->setDetails($paymentDetails);

        throw new HttpRedirect(
            $this->router->generate('webgriffe_sylius_pagolight_plugin_payment_process', [
                'tokenValue' => $order->getTokenValue(),
                '_locale' => $order->getLocaleCode(),
            ]),
        );
    }

    public function supports($request): bool
    {
        return $request instanceof Cancel && $request->getModel() instanceof SyliusPaymentInterface;
    }

    private function logInfo(SyliusPaymentInterface $payment, string $message, array $context = []): void
    {
        $this->logger->info(sprintf('[Payment #%s]: %s.', (string) $payment->getId(), $message,), $context);
    }
}
