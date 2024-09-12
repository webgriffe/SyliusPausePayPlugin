<?php

declare(strict_types=1);

namespace Webgriffe\SyliusPausePayPlugin\Controller;

use Payum\Core\Model\Identity;
use Payum\Core\Security\TokenInterface;
use Payum\Core\Storage\StorageInterface;
use Psr\Log\LoggerInterface;
use Sylius\Bundle\PayumBundle\Model\GatewayConfigInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Core\Model\PaymentMethodInterface;
use Sylius\Component\Core\Repository\PaymentRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Webgriffe\SyliusPausePayPlugin\Helper\PaymentDetailsHelper;
use Webgriffe\SyliusPausePayPlugin\Logger\LoggingHelperTrait;
use Webgriffe\SyliusPausePayPlugin\Payum\PausePayApi;
use Webmozart\Assert\Assert;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 *
 * @psalm-type PaymentDetails array{uuid: string, redirect_url: string, created_at: string, status?: string}
 */
final class PaymentController extends AbstractController
{
    use LoggingHelperTrait;

    public function __construct(
        private StorageInterface $tokenStorage,
        private RouterInterface $router,
        private PaymentRepositoryInterface $paymentRepository,
        private LoggerInterface $logger,
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

        $syliusPayment = $this->retrievePaymentFromToken($token);
        $paymentDetails = $syliusPayment->getDetails();
        if (!PaymentDetailsHelper::areValid($paymentDetails)) {
            throw $this->createAccessDeniedException();
        }

        $paymentStatusUrl = $this->router->generate(
            'webgriffe_sylius_pausepay_plugin_payment_status',
            ['payumToken' => $payumToken],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );
        $params = [
            'afterUrl' => $token->getAfterUrl(),
            'paymentStatusUrl' => $paymentStatusUrl,
            'redirectUrl' => PaymentDetailsHelper::getRedirectUrl($paymentDetails),
        ];
        $this->logInfo($syliusPayment, 'Showing process page to user.', $params);

        return $this->render('@WebgriffeSyliusPausePayPlugin/Process/index.html.twig', $params);
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

        $syliusPayment = $this->retrievePaymentFromToken($token);
        $paymentDetails = $syliusPayment->getDetails();
        if (!PaymentDetailsHelper::areValid($paymentDetails)) {
            throw $this->createAccessDeniedException();
        }

        $paymentStatus = PaymentDetailsHelper::getPaymentStatus($paymentDetails);

        $this->logInfo($syliusPayment, sprintf('Retrieved status "%s"', $paymentStatus ?? 'null'));

        return $this->json(['captured' => $paymentStatus !== null]);
    }

    private function retrievePaymentFromToken(TokenInterface $token): PaymentInterface
    {
        $paymentIdentity = $token->getDetails();
        Assert::isInstanceOf($paymentIdentity, Identity::class);

        /** @var PaymentInterface|null $syliusPayment */
        $syliusPayment = $this->paymentRepository->find($paymentIdentity->getId());
        if (!$syliusPayment instanceof PaymentInterface) {
            throw $this->createNotFoundException();
        }

        $this->assertPausePayPayment($syliusPayment);

        return $syliusPayment;
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
