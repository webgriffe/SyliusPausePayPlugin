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
use Webgriffe\SyliusPausePayPlugin\Logger\LoggingHelperTrait;
use Webmozart\Assert\Assert;

/**
 * @psalm-type PaymentDetails array{uuid: string, redirect_url: string, created_at: string, status?: string}
 */
final class CancelAction implements ActionInterface
{
    use LoggingHelperTrait;

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

        $paymentDetails = PaymentDetailsHelper::addPaymentStatus($paymentDetails, PaymentState::CANCELLED);
        $payment->setDetails($paymentDetails);

        $this->logInfo($payment, 'Redirecting the user to the Sylius PausePay waiting page.');

        throw new HttpRedirect(
            $this->router->generate('webgriffe_sylius_pausepay_plugin_payment_process', [
                'payumToken' => $order->getTokenValue(),
                '_locale' => $order->getLocaleCode(),
            ]),
        );
    }

    public function supports($request): bool
    {
        return $request instanceof Cancel && $request->getModel() instanceof SyliusPaymentInterface;
    }
}
