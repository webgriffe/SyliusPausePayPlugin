<?php

declare(strict_types=1);

namespace Webgriffe\SyliusPausePayPlugin\Controller;

use Payum\Core\Model\Identity;
use Payum\Core\Security\TokenInterface;
use Payum\Core\Storage\StorageInterface;
use Sylius\Bundle\PayumBundle\Model\GatewayConfigInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Core\Model\PaymentMethodInterface;
use Sylius\Component\Core\Repository\PaymentRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Webgriffe\SyliusPausePayPlugin\Helper\PaymentDetailsHelper;
use Webgriffe\SyliusPausePayPlugin\Payum\PausePayApi;
use Webmozart\Assert\Assert;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 *
 * @psalm-type PaymentDetails array{uuid: string, redirect_url: string, created_at: string, status?: string}
 */
final class PaymentController extends AbstractController
{
    public function __construct(
        private StorageInterface $tokenStorage,
        private RouterInterface $router,
        private PaymentRepositoryInterface $paymentRepository,
    ) {
    }

    public function processAction(string $payumToken): Response
    {
        if ($payumToken === '') {
            throw $this->createNotFoundException();
        }

        $token = $this->tokenStorage->find($payumToken);
        if (!$token instanceof TokenInterface) {
            throw $this->createNotFoundException();
        }

        $paymentDetails = $this->retrieveDetailsFromToken($token);

        $redirectUrl = PaymentDetailsHelper::getRedirectUrl($paymentDetails);
        $paymentStatusUrl = $this->router->generate(
            'webgriffe_sylius_pausepay_plugin_payment_status',
            ['payumToken' => $payumToken],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );

        return $this->render('@WebgriffeSyliusPausePayPlugin/Process/index.html.twig', [
            'afterUrl' => $token->getAfterUrl(),
            'paymentStatusUrl' => $paymentStatusUrl,
            'redirectUrl' => $redirectUrl,
        ]);
    }

    public function statusAction(string $payumToken): Response
    {
        if ($payumToken === '') {
            throw $this->createNotFoundException();
        }

        $token = $this->tokenStorage->find($payumToken);
        if (!$token instanceof TokenInterface) {
            throw $this->createNotFoundException();
        }

        $paymentDetails = $this->retrieveDetailsFromToken($token);
        $paymentStatus = PaymentDetailsHelper::getPaymentStatus($paymentDetails);

        return $this->json(['captured' => $paymentStatus !== null]);
    }

    /**
     * @return PaymentDetails
     */
    private function retrieveDetailsFromToken(TokenInterface $token): array
    {
        $paymentIdentity = $token->getDetails();
        Assert::isInstanceOf($paymentIdentity, Identity::class);

        /** @var PaymentInterface|null $syliusPayment */
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

        return $paymentDetails;
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
