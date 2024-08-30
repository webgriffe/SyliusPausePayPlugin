<?php

declare(strict_types=1);

namespace Webgriffe\SyliusPausePayPlugin\Payum\Action\Api;

use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\ApiAwareTrait;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Webgriffe\SyliusPagolightPlugin\Client\ClientInterface;
use Webgriffe\SyliusPausePayPlugin\Payum\PausePayApi;
use Webgriffe\SyliusPausePayPlugin\Payum\Request\Api\CreateOrder;

final class CreateOrderAction implements ActionInterface, GatewayAwareInterface, ApiAwareInterface
{
    use GatewayAwareTrait, ApiAwareTrait;

    public function __construct(
        private ClientInterface $client,
    ) {
        $this->apiClass = PausePayApi::class;
    }

    public function execute($request)
    {
    }

    public function supports($request): bool
    {
        return $request instanceof CreateOrder;
    }
}
