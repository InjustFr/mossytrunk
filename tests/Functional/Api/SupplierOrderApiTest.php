<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use App\Tests\Support\Json;
use App\Tests\Support\ProductTypesApi;
use App\Tests\Support\SignsInClient;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class SupplierOrderApiTest extends WebTestCase
{
    use SignsInClient;

    public function testOrderThenReceive(): void
    {
        $client = self::signedInClient();
        $supplierId = self::created($client, '/api/suppliers', ['name' => 'Imprimerie du Lac', 'contact' => 'lac@example.test']);
        $productId = self::created($client, '/api/products', ['name' => 'Sticker', 'sellingPrice' => 400, 'typeId' => ProductTypesApi::create($client)]);

        $orderId = self::created($client, '/api/supplier-orders', ['supplierId' => $supplierId, 'orderedOn' => '2026-09-01', 'lines' => [['productId' => $productId, 'quantity' => 100, 'totalPrice' => 2_000]]]);

        $client->jsonRequest('GET', "/api/supplier-orders/$orderId");
        $order = self::body($client);
        self::assertSame('ordered', $order['status']);
        self::assertSame('Imprimerie du Lac', Json::at($order, 'supplier', 'name'));

        $client->jsonRequest('POST', "/api/supplier-orders/$orderId/reception", ['lines' => [['lineId' => Json::string($order, 'lines', 0, 'id'), 'received' => 80]]]);
        self::assertResponseStatusCodeSame(204);

        $client->jsonRequest('GET', '/api/supplier-orders');
        self::assertSame(25, Json::at(self::body($client), 0, 'lines', 0, 'unitCost'));

        $client->jsonRequest('DELETE', "/api/supplier-orders/$orderId");
        self::assertResponseStatusCodeSame(422);
    }

    public function testInvalidOrderIsRejected(): void
    {
        $client = self::signedInClient();

        $client->jsonRequest('POST', '/api/supplier-orders', ['supplierId' => '', 'orderedOn' => 'demain', 'lines' => []]);

        self::assertResponseStatusCodeSame(422);
        self::assertSame(['supplierId', 'orderedOn', 'lines'], array_column(Json::array(self::body($client), 'violations'), 'propertyPath'));
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
