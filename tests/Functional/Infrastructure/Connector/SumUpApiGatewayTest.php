<?php

declare(strict_types=1);

namespace App\Tests\Functional\Infrastructure\Connector;

use App\Application\Integration\Exception\ServiceUnavailable;
use App\Infrastructure\Connector\SumUp\SumUpApiGateway;
use App\Infrastructure\Connector\SumUp\SumUpCredentials;
use App\Infrastructure\Connector\SumUp\SumUpPayloadMapper;
use App\Tests\Support\Json;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\JsonMockResponse;
use Symfony\Component\HttpClient\Response\MockResponse;

final class SumUpApiGatewayTest extends TestCase
{
    public function testFollowsPaginationAndLoadsProducts(): void
    {
        $requests = [];
        $responses = [
            new JsonMockResponse(['items' => [['id' => 'a', 'transaction_code' => 'T1', 'timestamp' => '2030-03-14T10:00:00Z', 'amount' => 10.0]], 'links' => [['rel' => 'next', 'href' => 'limit=100&oldest_ref=a&order=ascending']]]),
            new JsonMockResponse(['id' => 'a', 'transaction_code' => 'T1', 'timestamp' => '2030-03-14T10:00:00Z', 'amount' => 10.0, 'tip_amount' => 1.0, 'products' => [['name' => 'Sticker', 'price' => 4.0, 'price_with_vat' => 4.0, 'quantity' => 3]]]),
            new JsonMockResponse(['items' => [], 'links' => []]),
        ];
        $client = new MockHttpClient(static function (string $method, string $url, array $options) use (&$requests, &$responses): MockResponse {
            $requests[] = [$url, Json::at($options, 'normalized_headers', 'authorization', 0)];

            return array_shift($responses) ?? throw new \LogicException('Unexpected SumUp request.');
        }, 'https://api.sumup.com');

        $transactions = iterator_to_array((new SumUpApiGateway($client, new SumUpPayloadMapper()))->successfulPayments(new SumUpCredentials('sup_sk_test', 'MCODE')), false);

        self::assertCount(1, $transactions);
        self::assertSame('T1', $transactions[0]->id);
        self::assertSame(900, $transactions[0]->charged->amount(), 'tip excluded');
        self::assertSame('Sticker', $transactions[0]->lines[0]->name);
        self::assertSame(400, $transactions[0]->lines[0]->unitPrice->amount());
        self::assertSame(3, $transactions[0]->lines[0]->quantity);

        self::assertStringContainsString('/v2.1/merchants/MCODE/transactions/history?order=ascending&limit=100&statuses%5B%5D=SUCCESSFUL&types%5B%5D=PAYMENT', $requests[0][0]);
        self::assertSame('https://api.sumup.com/v2.1/merchants/MCODE/transactions?id=a', $requests[1][0]);
        self::assertStringContainsString('oldest_ref=a', $requests[2][0]);
        self::assertSame('Authorization: Bearer sup_sk_test', $requests[0][1]);
    }

    public function testHttpErrorsAreReported(): void
    {
        $client = new MockHttpClient(new MockResponse('{"message":"invalid token"}', ['http_code' => 401]), 'https://api.sumup.com');

        $this->expectException(ServiceUnavailable::class);
        iterator_to_array((new SumUpApiGateway($client, new SumUpPayloadMapper()))->successfulPayments(new SumUpCredentials('bad', 'MCODE')));
    }
}
