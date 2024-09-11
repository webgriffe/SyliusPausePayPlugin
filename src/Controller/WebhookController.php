<?php

declare(strict_types=1);

namespace Webgriffe\SyliusPausePayPlugin\Controller;

use Payum\Core\Model\Identity;
use Psr\Log\LoggerInterface;
use Sylius\Bundle\PayumBundle\Model\GatewayConfigInterface;
use Sylius\Bundle\PayumBundle\Model\PaymentSecurityTokenInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Core\Model\PaymentMethodInterface;
use Sylius\Component\Core\Repository\PaymentRepositoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
final class WebhookController extends AbstractController
{
    public function __construct(
        private SerializerInterface $serializer,
        private RepositoryInterface $paymentOrderRepository,
        private PaymentRepositoryInterface $paymentRepository,
        private LoggerInterface $logger,
    ) {
    }

    public function indexAction(Request $request): Response
    {
        $content = $request->getContent();
        Assert::string($content);

        $this->logger->info(sprintf('Received webhook call with payload: %s', $content));

        $payload = $this->serializer->deserialize($content, WebhookPayload::class, 'json');
        Assert::isInstanceOf($payload, WebhookPayload::class);

        // todo: introduce new entity PaymentOrder
        $paymentOrder = $this->paymentOrderRepository->findOneBy(['orderId' => $payload->getOrderID()]);
        /** @var PaymentSecurityTokenInterface $token */
        $token = $paymentOrder->getToken();

        $paymentIdentity = $token->getDetails();
        Assert::isInstanceOf($paymentIdentity, Identity::class);

        $syliusPayment = $this->paymentRepository->find($paymentIdentity->getId());
        Assert::nullOrIsInstanceOf($syliusPayment, PaymentInterface::class);
        if (!$syliusPayment instanceof PaymentInterface) {
            throw $this->createNotFoundException();
        }

        $this->assertPausePayPayment($syliusPayment);

        $paymentDetails = $syliusPayment->getDetails();
        if (!PaymentDetailsHelper::areValid($paymentDetails)) {
            throw $this->createAccessDeniedException();
        }

        PaymentDetailsHelper::assertPaymentDetailsAreValid($paymentDetails);

        $status = $payload->isSuccessful() ? PaymentState::SUCCESS : PaymentState::CANCELLED;

        $syliusPayment->setDetails(
            PaymentDetailsHelper::addPaymentStatus(
                $paymentDetails,
                $status,
            ),
        );

        return new Response();
    }

    private function assertPausePayPayment(PaymentInterface $syliusPayment): void
    {
        $paymentMethod = $syliusPayment->getMethod();
        if (!$paymentMethod instanceof PaymentMethodInterface) {
            throw $this->createAccessDeniedException();
        }

        $paymentGatewayConfig = $paymentMethod->getGatewayConfig();
        if (!$paymentGatewayConfig instanceof GatewayConfigInterface) {
            throw $this->createAccessDeniedException();
        }
        /** @psalm-suppress DeprecatedMethod */
        if ($paymentGatewayConfig->getFactoryName() !== PausePayApi::GATEWAY_CODE) {
            throw $this->createAccessDeniedException();
        }
    }
}
