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
use Psr\Log\LoggerInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\PaymentInterface as SyliusPaymentInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Webgriffe\SyliusPausePayPlugin\Client\ClientInterface;
use Webgriffe\SyliusPausePayPlugin\Client\ValueObject\Order;
use Webgriffe\SyliusPausePayPlugin\Payum\PausePayApi;
use Webmozart\Assert\Assert;

final class CaptureAction implements ActionInterface, GatewayAwareInterface, ApiAwareInterface
{
    use GatewayAwareTrait, ApiAwareTrait;

    public function __construct(
        private RouterInterface $router,
        private LoggerInterface $logger,
        private RequestStack $requestStack,
        private ClientInterface $client,
    ) {
        $this->apiClass = PausePayApi::class;
    }

    public function execute($request): void
    {
        RequestNotSupportedException::assertSupports($this, $request);
        Assert::isInstanceOf($request, Capture::class);

        /** @var SyliusPaymentInterface $payment */
        $payment = $request->getModel();

        /** @var string|int $paymentId */
        $paymentId = $payment->getId();
        $this->logInfo($payment, 'Start capture action', );

        $paymentDetails = $payment->getDetails();

        if ($paymentDetails !== []) {
            // todo
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

        // todo
        $order = new Order(1000, '000000012');
        $createOrderResult = $this->client->createOrder($order);

        $redirectUrl = $createOrderResult->getRedirectUrl();
        $this->logInfo(
            $payment,
            sprintf(
                'Redirecting the user to the PausePay redirect URL "%s".',
                $redirectUrl,
            ),
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
