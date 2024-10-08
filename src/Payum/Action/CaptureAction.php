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
use Sylius\Bundle\PayumBundle\Model\PaymentSecurityTokenInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\PaymentInterface as SyliusPaymentInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Webgriffe\SyliusPausePayPlugin\Client\ClientInterface;
use Webgriffe\SyliusPausePayPlugin\Client\PaymentState;
use Webgriffe\SyliusPausePayPlugin\Client\ValueObject\Response\CreateOrderResult;
use Webgriffe\SyliusPausePayPlugin\Factory\PaymentOrderFactoryInterface;
use Webgriffe\SyliusPausePayPlugin\Helper\PaymentDetailsHelper;
use Webgriffe\SyliusPausePayPlugin\Logger\LoggingHelperTrait;
use Webgriffe\SyliusPausePayPlugin\Mapper\OrderMapperInterface;
use Webgriffe\SyliusPausePayPlugin\Payum\PausePayApi;
use Webgriffe\SyliusPausePayPlugin\Repository\PaymentOrderRepositoryInterface;
use Webmozart\Assert\Assert;

/**
 * @psalm-suppress PropertyNotSetInConstructor Api and gateway are injected via container configuration
 */
final class CaptureAction implements ActionInterface, GatewayAwareInterface, GenericTokenFactoryAwareInterface, ApiAwareInterface
{
    use GatewayAwareTrait, GenericTokenFactoryAwareTrait, ApiAwareTrait, LoggingHelperTrait;

    public const DETAILS_ALREADY_POPULATED_EVENT_NAME = 'webgriffe.sylius_pausepay_plugin.action.capture.details_already_populated';

    public function __construct(
        private RouterInterface $router,
        private LoggerInterface $logger,
        private ClientInterface $client,
        private OrderMapperInterface $orderMapper,
        private PaymentOrderRepositoryInterface $paymentOrderRepository,
        private PaymentOrderFactoryInterface $paymentOrderFactory,
        private EventDispatcherInterface $eventDispatcher,
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
        $payment = $request->getModel();
        Assert::isInstanceOf($payment, SyliusPaymentInterface::class);
        $captureToken = $request->getToken();
        Assert::isInstanceOf($captureToken, TokenInterface::class);

        $this->logInfo($payment, 'Start capture action');

        $paymentDetails = $payment->getDetails();
        if ($paymentDetails !== []) {
            $this->eventDispatcher->dispatch(
                new GenericEvent($payment, ['paymentDetails' => $paymentDetails, 'captureToken' => $captureToken]),
                self::DETAILS_ALREADY_POPULATED_EVENT_NAME,
            );

            $this->redirectToPaymentProcessPage($payment, $captureToken, $paymentDetails);
        }

        $cancelToken = $this->createCancelToken($captureToken);
        $createOrderResult = $this->client->createOrder(
            $this->orderMapper->mapFromSyliusPayment(
                $payment,
                $captureToken->getTargetUrl(),
                $cancelToken->getTargetUrl(),
            ),
        );

        $this->logInfo(
            $payment,
            sprintf(
                'Payment order created on Pause Pay with result: %s',
                json_encode($createOrderResult->toArray(), \JSON_THROW_ON_ERROR),
            ),
        );

        $this->createNotifyTokenAndPersistAssociationWithPausePayPayment($payment, $captureToken, $createOrderResult);
        $payment->setDetails(PaymentDetailsHelper::createFromCreateOrderResult($createOrderResult));

        $redirectUrl = $createOrderResult->getRedirectUrl();
        $this->logInfo($payment, sprintf('Redirecting the user to the PausePay redirect URL "%s".', $redirectUrl));

        throw new HttpRedirect($redirectUrl);
    }

    public function supports($request): bool
    {
        return
            $request instanceof Capture &&
            $request->getModel() instanceof SyliusPaymentInterface;
    }

    private function createNotifyTokenAndPersistAssociationWithPausePayPayment(
        SyliusPaymentInterface $payment,
        TokenInterface $captureToken,
        CreateOrderResult $createOrderResult,
    ): void {
        $notifyToken = $this->tokenFactory->createNotifyToken($captureToken->getGatewayName(), $payment);
        Assert::isInstanceOf($notifyToken, PaymentSecurityTokenInterface::class);

        $paymentOrder = $this->paymentOrderFactory->createNew();
        $paymentOrder->setOrderId($createOrderResult->getUuid());
        $paymentOrder->setPaymentToken($notifyToken);
        $this->paymentOrderRepository->add($paymentOrder);
    }

    private function createCancelToken(TokenInterface $captureToken): TokenInterface
    {
        return $this->tokenFactory->createToken(
            $captureToken->getGatewayName(),
            $captureToken->getDetails(),
            'payum_cancel_do',
            [],
            $captureToken->getAfterUrl(),
        );
    }

    private function redirectToPaymentProcessPage(
        SyliusPaymentInterface $payment,
        TokenInterface $captureToken,
        array $paymentDetails,
    ): void {
        if (!PaymentDetailsHelper::areValid($paymentDetails)) {
            // todo: is it ok to cancel the payment here?
            $this->logError($payment, 'Payment details are already populated with others data. Cancel the payment.');
            $payment->setDetails(PaymentDetailsHelper::addPaymentStatus($paymentDetails, PaymentState::CANCELLED));

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
                'payumToken' => $captureToken->getHash(),
                '_locale' => $order->getLocaleCode(),
            ]),
        );
    }
}
