<?php

declare(strict_types=1);

namespace Webgriffe\SyliusPausePayPlugin\Client;

use GuzzleHttp\ClientInterface as GuzzleHttpClientInterface;
use Webgriffe\SyliusPausePayPlugin\Client\ValueObject\Order;
use Webgriffe\SyliusPausePayPlugin\Client\ValueObject\Response\CreateOrderResult;

final class Client implements ClientInterface
{
    // todo: handle sandbox
    public function __construct(
        private GuzzleHttpClientInterface $httpClient,
        private LoggerInterface $logger,
    ) {
    }

    public function createOrder(Order $order): CreateOrderResult
    {
        throw new \RuntimeException('Not implemented yet');
    }
}
