<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusPausePayPlugin\Integration\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Fidry\AliceDataFixtures\Loader\PurgerLoader;
use Payum\Bundle\PayumBundle\Controller\NotifyController;
use Payum\Core\Model\Identity;
use Payum\Core\Reply\HttpResponse;
use Payum\Core\Storage\StorageInterface;
use Sylius\Bundle\PayumBundle\Model\PaymentSecurityTokenInterface;
use Sylius\Component\Core\Model\Payment;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Core\Repository\PaymentRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Webgriffe\SyliusPausePayPlugin\Client\PaymentState;
use Webgriffe\SyliusPausePayPlugin\Payum\PausePayApi;
use Webgriffe\SyliusPausePayPlugin\ValueObject\WebhookPayload;

final class NotifyControllerTest extends KernelTestCase
{
    private const FIXTURE_BASE_DIR = __DIR__ . '/../../DataFixtures/ORM/resources/Controller/NotifyControllerTest';

    private NotifyController $controller;

    private PurgerLoader $fixtureLoader;

    private PaymentRepositoryInterface $paymentRepository;

    private StorageInterface $tokenStorage;

    private EntityManagerInterface $entityManager;

    private RequestStack $requestStack;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fixtureLoader = self::getContainer()->get('fidry_alice_data_fixtures.loader.doctrine');
        $this->controller = self::getContainer()->get(NotifyController::class);
        $this->paymentRepository = self::getContainer()->get('sylius.repository.payment');
        $this->tokenStorage = self::getContainer()->get('payum.security.token_storage');
        $this->entityManager = self::getContainer()->get('doctrine.orm.entity_manager');
        $this->requestStack = self::getContainer()->get('request_stack');
    }

    public function test_that_it_confirms_order_payment(): void
    {
        $this->fixtureLoader->load(
            [
                self::FIXTURE_BASE_DIR . '/' . $this->getName() . '.yaml',
                self::FIXTURE_BASE_DIR . '/customers.yaml',
                self::FIXTURE_BASE_DIR . '/products.yaml',
                self::FIXTURE_BASE_DIR . '/payment_methods.yaml',
                self::FIXTURE_BASE_DIR . '/channels.yaml',
            ],
        );

        /** @var PaymentInterface $payment */
        $payment = $this->paymentRepository->findAll()[0];
        $this->associateTokenToPayment($payment);

        $requestContent = $this->getPausePayNotifyPayload();
        $mainRequest = new Request(['gateway' => PausePayApi::GATEWAY_CODE], [], [], [], [], [], $requestContent);
        $this->requestStack->push($mainRequest);

        $this->controller->doUnsafeAction($mainRequest);

        $this->entityManager->refresh($payment);
        self::assertSame(PaymentState::SUCCESS, $payment->getDetails()['status']);
        self::assertSame(PaymentInterface::STATE_COMPLETED, $payment->getState());

        $order = $payment->getOrder();
        self::assertCount(1, $order->getPayments());
        // todo: check if notify token has been removed?
    }

    public function test_that_it_cancel_order_payment(): void
    {
        $this->fixtureLoader->load(
            [
                self::FIXTURE_BASE_DIR . '/' . $this->getName() . '.yaml',
                self::FIXTURE_BASE_DIR . '/customers.yaml',
                self::FIXTURE_BASE_DIR . '/products.yaml',
                self::FIXTURE_BASE_DIR . '/payment_methods.yaml',
                self::FIXTURE_BASE_DIR . '/channels.yaml',
            ],
        );

        /** @var PaymentInterface $payment */
        $payment = $this->paymentRepository->findAll()[0];
        $this->associateTokenToPayment($payment);

        $requestContent = $this->getPausePayNotifyPayload(WebhookPayload::EVENT_TYPE_KO);
        $mainRequest = new Request(['gateway' => PausePayApi::GATEWAY_CODE], [], [], [], [], [], $requestContent);
        $this->requestStack->push($mainRequest);

        $this->controller->doUnsafeAction($mainRequest);

        $this->entityManager->refresh($payment);
        self::assertSame(PaymentState::CANCELLED, $payment->getDetails()['status']);
        self::assertSame(PaymentInterface::STATE_CANCELLED, $payment->getState());

        $order = $payment->getOrder();
        self::assertCount(2, $order->getPayments());
        // todo: check if notify token has been removed?
    }

    public function test_that_it_returns_not_found_rsponse_when_pause_pay_order_is_not_found(): void
    {
        $this->fixtureLoader->load([self::FIXTURE_BASE_DIR . '/payment_methods.yaml',],);

        $requestContent = $this->getPausePayNotifyPayload(WebhookPayload::EVENT_TYPE_OK, 'non-existing-order-id');
        $mainRequest = new Request(['gateway' => PausePayApi::GATEWAY_CODE], [], [], [], [], [], $requestContent);
        $this->requestStack->push($mainRequest);

        $this->expectExceptionObject(new HttpResponse('Not found', 404));

        $this->controller->doUnsafeAction($mainRequest);
    }

    /**
     * this cannot be done in the fixture as the payment ID is not known in advance
     */
    private function associateTokenToPayment(PaymentInterface $payment): void
    {
        /** @var PaymentSecurityTokenInterface $token */
        $token = $this->tokenStorage->find('3ocZLKOx41X04m63TnS9nrBFP6CN6Tu_lHBPvpGwn1o');
        $token->setDetails(new Identity($payment->getId(), Payment::class));
        $this->tokenStorage->update($token);
    }

    private function getPausePayNotifyPayload(
        string $eventType = WebhookPayload::EVENT_TYPE_OK,
        string $orderId = '0b58e4e4-1edc-4f2c-991f-112db59e982d'
    ): string {
        return <<<JSON
{
    "orderID": "$orderId",
    "eventType": "$eventType",
    "eventID": "ed8a2511-f60b-4a2b-ac20-02c7a93ccd13",
    "createdAt": "2024-05-24T16:12:38Z"
}

JSON;
    }
}
