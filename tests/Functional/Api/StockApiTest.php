<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use App\Tests\Support\Json;
use App\Tests\Support\ProductTypesApi;
use App\Tests\Support\SignsInClient;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class StockApiTest extends WebTestCase
{
    use SignsInClient;

    public function testRestockAndCheckStockAfterAnEvent(): void
    {
        $client = self::signedInClient();
        $productId = self::created($client, '/api/products', ['name' => 'Sticker', 'sellingPrice' => 400, 'typeId' => ProductTypesApi::create($client), 'lowStockThreshold' => 3]);
        $eventId = self::created($client, '/api/events', ['name' => 'Japan Expo', 'location' => 'Villepinte', 'startDate' => '2026-07-09', 'endDate' => '2026-07-12']);

        $client->jsonRequest('POST', '/api/stock/restock', ['productId' => $productId, 'quantity' => 10, 'totalPaid' => 1_000]);
        self::assertResponseStatusCodeSame(204);

        $client->jsonRequest('GET', "/api/products/$productId/stock");
        $stock = self::body($client);
        self::assertSame(10, Json::at($stock, 0, 'onHand'));
        self::assertSame(100, Json::at($stock, 0, 'lots', 0, 'unitCost'));

        $client->jsonRequest('GET', "/api/events/$eventId/stock-sheet");
        self::assertSame(10, Json::at(self::body($client), 0, 'onHand'));

        $checkId = self::created($client, "/api/events/$eventId/stock-checks", ['items' => [['productId' => $productId, 'counted' => 8]]]);

        $client->jsonRequest('GET', "/api/events/$eventId/stock-checks");
        $check = Json::array(self::body($client), 0);
        self::assertSame(2, $check['unexplainedUnits']);
        self::assertSame(800, $check['missedSales']);

        $client->jsonRequest('POST', '/api/stock-checks/'.$checkId.'/lines/'.Json::string($check, 'lines', 0, 'id').'/dismissal');
        self::assertResponseStatusCodeSame(204);

        $client->jsonRequest('GET', '/api/products');
        $product = Json::array(self::body($client), 0);
        self::assertSame(8, $product['onHand']);
        self::assertSame(3, $product['lowStockThreshold']);
    }

    public function testInvalidRestockIsRejected(): void
    {
        $client = self::signedInClient();
        $productId = self::created($client, '/api/products', ['name' => 'Sticker', 'sellingPrice' => 400, 'typeId' => ProductTypesApi::create($client)]);

        $client->jsonRequest('POST', '/api/stock/restock', ['productId' => $productId, 'quantity' => 0, 'totalPaid' => -1]);

        self::assertResponseStatusCodeSame(422);
        self::assertSame(['quantity', 'totalPaid'], array_column(Json::array(self::body($client), 'violations'), 'propertyPath'));
    }

    public function testRestockingAVariantOfAProductWithoutVariantsIsRejected(): void
    {
        $client = self::signedInClient();
        $productId = self::created($client, '/api/products', ['name' => 'Sticker', 'sellingPrice' => 400, 'typeId' => ProductTypesApi::create($client)]);

        $client->jsonRequest('POST', '/api/stock/restock', ['productId' => $productId, 'variant' => 'Rouge', 'quantity' => 2, 'totalPaid' => 100]);

        self::assertResponseStatusCodeSame(422);
    }

    public function testNegativeLowStockThresholdIsRejected(): void
    {
        $client = self::signedInClient();

        $client->jsonRequest('POST', '/api/products', ['name' => 'Sticker', 'sellingPrice' => 400, 'typeId' => ProductTypesApi::create($client), 'lowStockThreshold' => -1]);

        self::assertResponseStatusCodeSame(422);
        self::assertSame(['lowStockThreshold'], array_column(Json::array(self::body($client), 'violations'), 'propertyPath'));
    }

    /**
     * @param array<string, mixed> $body
     */
    private static function created(KernelBrowser $client, string $uri, array $body): string
    {
        $client->jsonRequest('POST', $uri, $body);
        self::assertResponseStatusCodeSame(201);

        return Json::string(self::body($client), 'id');
    }

    /**
     * @return array<mixed>
     */
    private static function body(KernelBrowser $client): array
    {
        return Json::decode((string) $client->getResponse()->getContent());
    }
}
