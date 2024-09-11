<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusPausePayPlugin\Integration\Controller;

use Fidry\AliceDataFixtures\Loader\PurgerLoader;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Core\Repository\PaymentRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Webgriffe\SyliusPausePayPlugin\Controller\WebhookController;

final class WebhookControllerTest extends KernelTestCase
{
    private const FIXTURE_BASE_DIR = __DIR__ . '/../../DataFixtures/ORM/resources/Controller/WebhookControllerTest';

    private WebhookController $webhookController;

    private PurgerLoader $fixtureLoader;

    private PaymentRepositoryInterface $paymentRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fixtureLoader = self::getContainer()->get('fidry_alice_data_fixtures.loader.doctrine');
        $this->webhookController = self::getContainer()->get('webgriffe_sylius_pausepay.controller.webhook');
        $this->paymentRepository = self::getContainer()->get('sylius.repository.payment');
    }

    public function test_that_it_confirms_order_payment(): void
    {
        $this->fixtureLoader->load(
            [
                self::FIXTURE_BASE_DIR . '/test_that_it_confirms_order_payment.yaml',
                self::FIXTURE_BASE_DIR . '/customers.yaml',
                self::FIXTURE_BASE_DIR . '/products.yaml',
                self::FIXTURE_BASE_DIR . '/payment_methods.yaml',
            ],
        );

        /** @var PaymentInterface $payment */
        $payment = $this->paymentRepository->findAll()[0];

        $content = <<<JSON
{
    "orderID":"0b58e4e4-1edc-4f2c-991f-112db59e982d",
    "eventType": "order.ok",
    "eventID":"ed8a2511-f60b-4a2b-ac20-02c7a93ccd13",
    "createdAt":"2024-05-24T16:12:38Z"
}

JSON;

        $this->webhookController->indexAction(new Request([], [], [], [], [], [], $content));

        self::assertSame(PaymentInterface::STATE_COMPLETED, $payment->getState());
    }
}
