<?php

declare(strict_types=1);

namespace Webgriffe\SyliusPausePayPlugin\Client;

use DateTimeImmutable;
use GuzzleHttp\ClientInterface as GuzzleHttpClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\ServerRequest;
use JsonException;
use Psr\Log\LoggerInterface;
use Webgriffe\SyliusPausePayPlugin\Client\Exception\ClientException;
use Webgriffe\SyliusPausePayPlugin\Client\Exception\OrderCreateFailedException;
use Webgriffe\SyliusPausePayPlugin\Client\ValueObject\Order;
use Webgriffe\SyliusPausePayPlugin\Client\ValueObject\Response\CreateOrderResult;
use Webgriffe\SyliusPausePayPlugin\Provider\ConfigurationProviderInterface;

final class Client implements ClientInterface
{
    public function __construct(
        private GuzzleHttpClientInterface $httpClient,
        private ConfigurationProviderInterface $configurationProvider,
        private LoggerInterface $logger,
        private string $productionUrl,
        private string $sandboxUrl,
        private string $sandboxApiKey,
    ) {
    }

    public function createOrder(Order $order): CreateOrderResult
    {
        try {
            $bodyParams = json_encode($order->toArrayParams(), \JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            $message = 'Malformed order create request body.';
            $this->logger->error($message, ['exception' => $e]);

            throw new OrderCreateFailedException($message, 0, $e, );
        }

        $this->logger->debug('Create order request body: ' . $bodyParams);

        $request = new ServerRequest(
            'POST',
            $this->getCreateOrderUrl(),
            [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'X-PausePay-Key' => $this->getApiKey(),
            ],
            $bodyParams,
        );

        try {
            $response = $this->httpClient->send($request);
        } catch (GuzzleException $e) {
            $this->logger->error($e->getMessage(), ['exception' => $e]);

            throw new ClientException($e->getMessage(), $e->getCode(), $e);
        }

        $bodyContents = $response->getBody()->getContents();
        $this->logger->debug('Create order request response: ' . $bodyContents);

        if ($response->getStatusCode() !== 201) {
            $message = sprintf(
                'Unexpected create order response status code: %s - "%s".',
                $response->getStatusCode(),
                $response->getReasonPhrase(),
            );
            $this->logger->error($message);

            throw new OrderCreateFailedException($message, $response->getStatusCode(), );
        }

        try {
            /** @var array{redirect_url: string, id: string} $serializedResponse */
            $serializedResponse = json_decode($bodyContents, true, 512, \JSON_THROW_ON_ERROR, );
        } catch (JsonException $e) {
            $message = sprintf('Malformed create order response body: "%s".', $bodyContents, );
            $this->logger->error($message, ['exception' => $e]);

            throw new OrderCreateFailedException($message, $response->getStatusCode(), $e, );
        }

        return new CreateOrderResult(
            $serializedResponse['redirect_url'],
            $serializedResponse['id'],
            new DateTimeImmutable(),
        );
    }

    private function getCreateOrderUrl(): string
    {
        return sprintf('%s/order/', $this->getBaseUrl());
    }

    private function getBaseUrl(): string
    {
        $url = $this->configurationProvider->isSandbox() ? $this->sandboxUrl : $this->productionUrl;

        return trim(rtrim($url, '/'));
    }

    private function getApiKey(): string
    {
        $apiKey = $this->configurationProvider->isSandbox() ?
            $this->sandboxApiKey :
            $this->configurationProvider->getApiKey();

        return trim($apiKey);
    }
}
