<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use App\Tests\Support\ActsAsUser;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class SupplierOrderApiTest extends WebTestCase
{
    use ActsAsUser;

    public function testOrderThenReceive(): void
    {
        $client = self::signedInClient();
        $supplierId = self::created($client, '/api/suppliers', ['name' => 'Imprimerie du Lac', 'contact' => 'lac@example.test']);
        $productId = self::created($client, '/api/products', ['name' => 'Sticker', 'sellingPrice' => 400]);

        $orderId = self::created($client, '/api/supplier-orders', ['supplierId' => $supplierId, 'orderedOn' => '2026-09-01', 'lines' => [['productId' => $productId, 'quantity' => 100, 'totalPrice' => 2_000]]]);

        $client->jsonRequest('GET', "/api/supplier-orders/$orderId");
        $order = self::body($client);
        self::assertSame('ordered', $order['status']);
        self::assertSame('Imprimerie du Lac', $order['supplier']['name']);

        $client->jsonRequest('POST', "/api/supplier-orders/$orderId/reception", ['lines' => [['lineId' => $order['lines'][0]['id'], 'received' => 80]]]);
        self::assertResponseStatusCodeSame(204);

        $client->jsonRequest('GET', '/api/supplier-orders');
        self::assertSame(25, self::body($client)[0]['lines'][0]['unitCost']);

        $client->jsonRequest('DELETE', "/api/supplier-orders/$orderId");
        self::assertResponseStatusCodeSame(422);
    }

    public function testInvalidOrderIsRejected(): void
    {
        $client = self::signedInClient();

        $client->jsonRequest('POST', '/api/supplier-orders', ['supplierId' => '', 'orderedOn' => 'demain', 'lines' => []]);

        self::assertResponseStatusCodeSame(422);
        self::assertSame(['supplierId', 'orderedOn', 'lines'], array_column(self::body($client)['violations'], 'propertyPath'));
    }

    /**
     * @param array<string, mixed> $body
     */
    private static function created(KernelBrowser $client, string $uri, array $body): string
    {
        $client->jsonRequest('POST', $uri, $body);
        self::assertResponseStatusCodeSame(201);

        return self::body($client)['id'];
    }

    /**
     * @return array<mixed>
     */
    private static function body(KernelBrowser $client): array
    {
        return json_decode((string) $client->getResponse()->getContent(), true);
    }
}
