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
use Psr\Log\LoggerInterface;
use Sylius\Bundle\PayumBundle\Model\PaymentSecurityTokenInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;
use Webgriffe\SyliusPausePayPlugin\Entity\PaymentOrderInterface;
use Webgriffe\SyliusPausePayPlugin\Repository\PaymentOrderRepositoryInterface;
use Webgriffe\SyliusPausePayPlugin\ValueObject\WebhookPayload;
use Webmozart\Assert\Assert;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 *
 * @psalm-type PaymentDetails array{uuid: string, redirect_url: string, created_at: string, status?: string}
 */
class NotifyNullAction implements ActionInterface, GatewayAwareInterface
{
    use GatewayAwareTrait;

    public function __construct(
        private SerializerInterface $serializer,
        private PaymentOrderRepositoryInterface $paymentOrderRepository,
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

        $this->logger->info('Start notify null action');
        $this->gateway->execute($httpRequest = new GetHttpRequest());
        $notifyToken = $this->retrieveNotifyTokenFromRequest($httpRequest);
        $this->logger->info(sprintf('Calling notify action with token: %s', $notifyToken->getHash()));
        $this->gateway->execute(new Notify($notifyToken));
    }

    public function supports($request): bool
    {
        return $request instanceof Notify && null === $request->getModel();
    }

    private function retrieveNotifyTokenFromRequest(GetHttpRequest $httpRequest): PaymentSecurityTokenInterface
    {
        // todo: validate signature in X-Pausepay-Signature
        $content = $httpRequest->content;

        $this->logger->info(sprintf('Received notify null action call with payload: %s', $content));

        $payload = $this->serializer->deserialize($content, WebhookPayload::class, 'json');
        Assert::isInstanceOf($payload, WebhookPayload::class);

        $pausePayOrderId = $payload->getOrderID();
        $paymentOrder = $this->paymentOrderRepository->findOneByPausePayOrderId($pausePayOrderId);
        if (!$paymentOrder instanceof PaymentOrderInterface) {
            // todo: info or error?
            $this->logger->info(sprintf('PaymentOrder with PausePay order ID "%s" not found', $pausePayOrderId));

            throw new HttpResponse('Not found', Response::HTTP_NOT_FOUND);
        }

        return $paymentOrder->getPaymentToken();
    }
}
