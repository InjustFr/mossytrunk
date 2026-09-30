<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use App\Tests\Support\Json;
use App\Tests\Support\ProductTypesApi;
use App\Tests\Support\SignsInClient;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class OrderApiTest extends WebTestCase
{
    use SignsInClient;

    public function testPlaceListAndShowOrder(): void
    {
        $client = self::signedInClient();
        $this->post($client, '/api/events', ['name' => 'Japan Expo', 'location' => 'Villepinte', 'startDate' => '2026-07-09', 'endDate' => '2026-07-12']);
        $product = $this->post($client, '/api/products', ['name' => 'T-shirt', 'sellingPrice' => 2_000, 'variants' => ['S', 'M'], 'typeId' => ProductTypesApi::create($client)])['id'];

        $order = $this->post($client, '/api/orders', ['placedAt' => '2026-07-10T15:30', 'lines' => [['productId' => $product, 'variant' => 'M', 'quantity' => 2]]]);
        self::assertStringStartsWith('CMD-20260710-', Json::string($order, 'reference'));

        $client->jsonRequest('GET', '/api/orders');
        $orders = Json::decode((string) $client->getResponse()->getContent());
        self::assertSame(4_000, Json::at($orders, 0, 'total'));
        self::assertSame('Japan Expo', Json::at($orders, 0, 'eventName'));

        $client->jsonRequest('GET', '/api/orders/'.Json::string($order, 'id'));
        self::assertSame('T-shirt — M', Json::at(Json::decode((string) $client->getResponse()->getContent()), 'lines', 0, 'label'));
    }

    public function testDeleteAllOrders(): void
    {
        $client = self::signedInClient();
        $this->post($client, '/api/events', ['name' => 'Japan Expo', 'location' => 'Villepinte', 'startDate' => '2026-07-09', 'endDate' => '2026-07-12']);
        $product = $this->post($client, '/api/products', ['name' => 'Sticker', 'sellingPrice' => 400, 'typeId' => ProductTypesApi::create($client)])['id'];
        $this->post($client, '/api/orders', ['placedAt' => '2026-07-10T15:30', 'lines' => [['productId' => $product, 'quantity' => 1]]]);

        $client->jsonRequest('DELETE', '/api/orders');
        self::assertResponseIsSuccessful();
        self::assertSame(['deleted' => 1], Json::decode((string) $client->getResponse()->getContent()));

        $client->jsonRequest('GET', '/api/orders');
        self::assertSame([], Json::decode((string) $client->getResponse()->getContent()));
    }

    public function testLineViolationsAreReported(): void
    {
        $client = self::signedInClient();

        $client->jsonRequest('POST', '/api/orders', ['placedAt' => '2026-07-10T15:30', 'lines' => [['productId' => 'nope', 'quantity' => 0]]]);

        self::assertResponseStatusCodeSame(422);
        $paths = array_column(Json::array(Json::decode((string) $client->getResponse()->getContent()), 'violations'), 'propertyPath');
        self::assertEqualsCanonicalizing(['lines[0].productId', 'lines[0].quantity'], $paths);
    }

    /**
     * @param array<string, mixed> $body
     *
     * @return array<mixed>
     */
    private function post(KernelBrowser $client, string $url, array $body): array
    {
        $client->jsonRequest('POST', $url, $body);
        self::assertResponseStatusCodeSame(201, (string) $client->getResponse()->getContent());

        return Json::decode((string) $client->getResponse()->getContent());
    }
}
