<?php

declare(strict_types=1);

namespace Webgriffe\SyliusPausePayPlugin\Payum\Action;

use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\ApiAwareTrait;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Reply\HttpRedirect;
use Payum\Core\Request\Capture;
use Payum\Core\Security\GenericTokenFactoryAwareInterface;
use Payum\Core\Security\GenericTokenFactoryAwareTrait;
use Payum\Core\Security\TokenInterface;
use Psr\Log\LoggerInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\PaymentInterface as SyliusPaymentInterface;
use Symfony\Component\Routing\RouterInterface;
use Webgriffe\SyliusPausePayPlugin\Client\ClientInterface;
use Webgriffe\SyliusPausePayPlugin\Client\PaymentState;
use Webgriffe\SyliusPausePayPlugin\Helper\PaymentDetailsHelper;
use Webgriffe\SyliusPausePayPlugin\Mapper\OrderMapperInterface;
use Webgriffe\SyliusPausePayPlugin\Payum\PausePayApi;
use Webmozart\Assert\Assert;

/**
 * @psalm-suppress PropertyNotSetInConstructor Api and gateway are injected via container configuration
 */
final class CaptureAction implements ActionInterface, GatewayAwareInterface, GenericTokenFactoryAwareInterface, ApiAwareInterface
{
    use GatewayAwareTrait, GenericTokenFactoryAwareTrait, ApiAwareTrait;

    public function __construct(
        private RouterInterface $router,
        private LoggerInterface $logger,
        private ClientInterface $client,
        private OrderMapperInterface $orderMapper,
    ) {
        $this->apiClass = PausePayApi::class;
    }

    /**
     * @param Capture|mixed $request
     */
    public function execute($request): void
    {
        RequestNotSupportedException::assertSupports($this, $request);
        Assert::isInstanceOf($request, Capture::class);

        /** @var SyliusPaymentInterface $payment */
        $payment = $request->getModel();

        $this->logInfo($payment, 'Start capture action', );

        $paymentDetails = $payment->getDetails();

        if ($paymentDetails !== []) {
            if (!PaymentDetailsHelper::areValid($paymentDetails)) {
                $this->logger->error('Payment details are already populated with others data. Maybe this payment should be marked as error');
                $payment->setDetails(PaymentDetailsHelper::addPaymentStatus(
                    $paymentDetails,
                    PaymentState::CANCELLED,
                ));

                return;
            }

            $this->logInfo(
                $payment,
                'PausePay payment details are already valued, so no need to continue here.' .
                ' Redirecting the user to the Sylius PausePay Payments waiting page.',
            );

            $order = $payment->getOrder();
            Assert::isInstanceOf($order, OrderInterface::class);

            throw new HttpRedirect(
                $this->router->generate('webgriffe_sylius_pausepay_plugin_payment_process', [
                    'tokenValue' => $order->getTokenValue(),
                    '_locale' => $order->getLocaleCode(),
                ]),
            );
        }

        // todo: verify urls
        $captureToken = $request->getToken();
        Assert::isInstanceOf($captureToken, TokenInterface::class);

        $captureUrl = $captureToken->getTargetUrl();

        $cancelToken = $this->tokenFactory->createToken(
            $captureToken->getGatewayName(),
            $captureToken->getDetails(),
            'payum_cancel_do',
            [],
            $captureToken->getAfterUrl(),
        );
        $cancelUrl = $cancelToken->getTargetUrl();

        $createOrderResult = $this->client->createOrder(
            $this->orderMapper->mapFromSyliusPayment($payment, $captureUrl, $cancelUrl),
        );

        $redirectUrl = $createOrderResult->getRedirectUrl();
        $this->logInfo(
            $payment,
            sprintf(
                'Redirecting the user to the PausePay redirect URL "%s".',
                $redirectUrl,
            ),
        );

        $payment->setDetails(
            PaymentDetailsHelper::createFromContractCreateResult($createOrderResult),
        );

        throw new HttpRedirect($redirectUrl);
    }

    public function supports($request): bool
    {
        return
            $request instanceof Capture &&
            $request->getModel() instanceof SyliusPaymentInterface;
    }

    private function logInfo(SyliusPaymentInterface $payment, string $message, array $context = []): void
    {
        $this->logger->info(sprintf('[Payment #%s]: %s.', (string) $payment->getId(), $message, ), $context);
    }
}
