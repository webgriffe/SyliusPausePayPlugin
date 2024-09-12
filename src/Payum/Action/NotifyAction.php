<?php

declare(strict_types=1);

namespace Webgriffe\SyliusPausePayPlugin\Payum\Action;

use Payum\Core\Action\ActionInterface;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Reply\HttpResponse;
use Payum\Core\Request\GetHttpRequest;
use Payum\Core\Request\Notify;
use Payum\Core\Security\TokenInterface;
use Psr\Log\LoggerInterface;
use Sylius\Bundle\PayumBundle\Model\GatewayConfigInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Core\Model\PaymentInterface as SyliusPaymentInterface;
use Sylius\Component\Core\Model\PaymentMethodInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;
use Webgriffe\SyliusPausePayPlugin\Client\PaymentState;
use Webgriffe\SyliusPausePayPlugin\Helper\PaymentDetailsHelper;
use Webgriffe\SyliusPausePayPlugin\Logger\LoggingHelperTrait;
use Webgriffe\SyliusPausePayPlugin\Payum\PausePayApi;
use Webgriffe\SyliusPausePayPlugin\ValueObject\WebhookPayload;
use Webmozart\Assert\Assert;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 *
 * @psalm-type PaymentDetails array{uuid: string, redirect_url: string, created_at: string, status?: string}
 */
class NotifyAction implements ActionInterface, GatewayAwareInterface
{
    use GatewayAwareTrait, LoggingHelperTrait;

    public function __construct(
        private SerializerInterface $serializer,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @param Notify|mixed $request
     */
    public function execute($request): void
    {
        RequestNotSupportedException::assertSupports($this, $request);
        Assert::isInstanceOf($request, Notify::class);
        $payment = $request->getModel();
        Assert::isInstanceOf($payment, SyliusPaymentInterface::class);
        $notifyToken = $request->getToken();
        Assert::isInstanceOf($notifyToken, TokenInterface::class);

        $this->logInfo($payment, 'Start notify action');

        $this->gateway->execute($httpRequest = new GetHttpRequest());
        $content = $httpRequest->content;

        $this->logInfo($payment, sprintf('Notify action payload: %s', $content));

        $payload = $this->serializer->deserialize($content, WebhookPayload::class, 'json');
        Assert::isInstanceOf($payload, WebhookPayload::class);

        $this->assertPausePayPayment($payment);

        $paymentDetails = $payment->getDetails();
        if (!PaymentDetailsHelper::areValid($paymentDetails)) {
            // todo: is it ok to cancel the payment here?
            $this->logError($payment, 'Payment details are already populated with others data. Cancel the payment.');
            $payment->setDetails(PaymentDetailsHelper::addPaymentStatus($paymentDetails, PaymentState::CANCELLED));

            return;
        }

        $status = $payload->isSuccessful() ? PaymentState::SUCCESS : PaymentState::CANCELLED;
        $payment->setDetails(PaymentDetailsHelper::addPaymentStatus($paymentDetails, $status));

        $this->logInfo($payment, sprintf('Saved payment status: %s', $status));
    }

    public function supports($request): bool
    {
        return $request instanceof Notify && $request->getModel() instanceof SyliusPaymentInterface;
    }

    private function assertPausePayPayment(PaymentInterface $syliusPayment): void
    {
        $paymentMethod = $syliusPayment->getMethod();
        if (!$paymentMethod instanceof PaymentMethodInterface) {
            $this->logError($syliusPayment, 'Payment method not found');

            throw new HttpResponse('Access denied', Response::HTTP_FORBIDDEN);
        }

        $paymentGatewayConfig = $paymentMethod->getGatewayConfig();
        if (!$paymentGatewayConfig instanceof GatewayConfigInterface) {
            $this->logError($syliusPayment, 'Payment gateway config not found');

            throw new HttpResponse('Access denied', Response::HTTP_FORBIDDEN);
        }
        /** @psalm-suppress DeprecatedMethod */
        if ($paymentGatewayConfig->getFactoryName() !== PausePayApi::GATEWAY_CODE) {
            $this->logError($syliusPayment, 'Payment gateway is not Pause Pay');

            throw new HttpResponse('Access denied', Response::HTTP_FORBIDDEN);
        }
    }
}
