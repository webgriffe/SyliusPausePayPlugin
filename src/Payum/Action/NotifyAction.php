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
use Symfony\Component\Serializer\SerializerInterface;
use Webgriffe\SyliusPausePayPlugin\Client\PaymentState;
use Webgriffe\SyliusPausePayPlugin\Helper\PaymentDetailsHelper;
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
    use GatewayAwareTrait;

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

        /** @var SyliusPaymentInterface $syliusPayment */
        $syliusPayment = $request->getModel();

        $this->logInfo($syliusPayment, 'Start notify action');

        $notifyToken = $request->getToken();
        Assert::isInstanceOf($notifyToken, TokenInterface::class);

        $this->gateway->execute($httpRequest = new GetHttpRequest());
        $content = $httpRequest->content;

        $this->logInfo($syliusPayment, sprintf('Received notify action call with payload: %s', $content));

        $payload = $this->serializer->deserialize($content, WebhookPayload::class, 'json');
        Assert::isInstanceOf($payload, WebhookPayload::class);

        $this->assertPausePayPayment($syliusPayment);

        $paymentDetails = $syliusPayment->getDetails();
        if (!PaymentDetailsHelper::areValid($paymentDetails)) {
            // todo
            throw new HttpResponse('Not found', 404);
        }

        $status = $payload->isSuccessful() ? PaymentState::SUCCESS : PaymentState::CANCELLED;
        $syliusPayment->setDetails(PaymentDetailsHelper::addPaymentStatus($paymentDetails, $status));

        $this->logInfo($syliusPayment, sprintf('Saved payment status: %s', $status));
    }

    public function supports($request): bool
    {
        return $request instanceof Notify && $request->getModel() instanceof SyliusPaymentInterface;
    }

    private function assertPausePayPayment(PaymentInterface $syliusPayment): void
    {
        $paymentMethod = $syliusPayment->getMethod();
        if (!$paymentMethod instanceof PaymentMethodInterface) {
            throw new HttpResponse('Access denied', 403);
        }

        $paymentGatewayConfig = $paymentMethod->getGatewayConfig();
        if (!$paymentGatewayConfig instanceof GatewayConfigInterface) {
            throw new HttpResponse('Access denied', 403);
        }
        /** @psalm-suppress DeprecatedMethod */
        if ($paymentGatewayConfig->getFactoryName() !== PausePayApi::GATEWAY_CODE) {
            throw new HttpResponse('Access denied', 403);
        }
    }

    private function logInfo(SyliusPaymentInterface $payment, string $message, array $context = []): void
    {
        $this->logger->info(sprintf('[Payment #%s]: %s.', (string) $payment->getId(), $message, ), $context);
    }
}
