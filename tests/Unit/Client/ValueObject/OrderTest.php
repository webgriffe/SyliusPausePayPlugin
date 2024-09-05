<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusPausePayPlugin\Unit\Client\ValueObject;

use PHPUnit\Framework\TestCase;
use Webgriffe\SyliusPausePayPlugin\Client\ValueObject\Order;
use Webgriffe\SyliusPausePayPlugin\Client\ValueObject\OrderItem;

final class OrderTest extends TestCase
{
    public function test_to_array(): void
    {
        $order = new Order(
            100.0,
            '123',
            new \DateTimeImmutable('2024-09-01 12:23:56'),
            'Description',
            'Remittance',
            'http://ok.com',
            'http://ko.com',
            'Buyer Name',
            'VAT123',
            'support@webgriffe.com',
            'pec@webgriffe.com',
            [
                new OrderItem('Product 1', 1, 10.0),
                new OrderItem('Product 2', 2, 20.0),
            ]
        );

        self::assertSame(
            [
                'amount' => 100.0,
                'number' => '123',
                'issueDate' => '01/09/2024 12:23',
                'description' => 'Description',
                'remittance' => 'Remittance',
                'okRedirect' => 'http://ok.com',
                'koRedirect' => 'http://ko.com',
                'allowToEditRemittance' => false,
                'buyerInfo' => [
                    'name' => 'Buyer Name',
                    'vatCode' => 'VAT123',
                    'email' => 'support@webgriffe.com',
                    'pec' => 'pec@webgriffe.com',
                ],
                'items' => [
                    [
                        'description' => 'Product 1',
                        'quantity' => 1,
                        'amount' => 10.0,
                    ],
                    [
                        'description' => 'Product 2',
                        'quantity' => 2,
                        'amount' => 20.0,
                    ],
                ],
                'allowSCTPayment' => true,
            ],
            $order->toArray()
        );
    }
}
