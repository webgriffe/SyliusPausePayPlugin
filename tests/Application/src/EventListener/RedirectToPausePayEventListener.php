<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusPausePayPlugin\App\EventListener;

use Payum\Core\Reply\HttpRedirect;
use Sylius\Component\Core\Model\PaymentInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Webgriffe\SyliusPausePayPlugin\Helper\PaymentDetailsHelper;

/**
 * @psalm-type PaymentDetails array{uuid: string, redirect_url: string, created_at: string, status?: string}
 */
final class RedirectToPausePayEventListener
{
    public function __construct(
        private RequestStack $requestStack,
        private UrlGeneratorInterface $urlGenerator
    ) {
    }

    public function redirectToPausePayWhenFromMyAccount(GenericEvent $event): void
    {
        /** @var PaymentInterface $payment */
        $payment = $event->getSubject();
        if ($payment === null) {
            return;
        }

        $order = $payment->getOrder();
        if ($order === null) {
            return;
        }

        $paymentDetails = $payment->getDetails();
        if (!PaymentDetailsHelper::areValid($paymentDetails)) {
            return;
        }

        $request = $this->requestStack->getMainRequest();
        if ($request === null) {
            return;
        }

        $haystack = $request->headers->get('referer');
        if ($haystack === null || $haystack === '') {
            return;
        }

        $shopOrderUrl = $this->urlGenerator->generate(
            'sylius_shop_order_show',
            ['tokenValue' => $order->getTokenValue()]
        );

        if (!str_contains($haystack, $shopOrderUrl)) {
            return;
        }

        throw new HttpRedirect($paymentDetails['redirect_url']);
    }
}
