<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use App\Tests\Support\ActsAsUser;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class SellingPriceApiTest extends WebTestCase
{
    use ActsAsUser;

    public function testFixAnImportedPriceThenRecordThePastOne(): void
    {
        $client = self::signedInClient();
        $client->jsonRequest('POST', '/api/products', ['name' => 'Sticker', 'sellingPrice' => 9_999]);
        $productId = self::body($client)['id'];
        $imported = self::product($client, $productId)['priceHistory'][0];

        $client->jsonRequest('PUT', "/api/products/$productId/prices/{$imported['id']}", ['price' => 450, 'since' => '2026-09-01']);
        self::assertResponseStatusCodeSame(204);
        $client->jsonRequest('POST', "/api/products/$productId/prices", ['price' => 400, 'since' => '2026-07-01']);
        self::assertResponseStatusCodeSame(204);

        $product = self::product($client, $productId);
        self::assertSame(450, $product['product']['sellingPrice']);
        self::assertSame([['price' => 450, 'sinceDay' => '2026-09-01'], ['price' => 400, 'sinceDay' => '2026-07-01']], array_map(
            static fn (array $change): array => ['price' => $change['price'], 'sinceDay' => $change['sinceDay']],
            $product['priceHistory'],
        ));

        $client->jsonRequest('DELETE', "/api/products/$productId/prices/{$product['priceHistory'][0]['id']}");
        self::assertSame(400, self::product($client, $productId)['product']['sellingPrice']);

        $client->jsonRequest('POST', "/api/products/$productId/prices", ['price' => 500, 'since' => '2099-01-01']);
        self::assertResponseStatusCodeSame(422);
    }

    /**
     * @return array<mixed>
     */
    private static function product(KernelBrowser $client, string $id): array
    {
        $client->jsonRequest('GET', "/api/products/$id");

        return self::body($client);
    }

    /**
     * @return array<mixed>
     */
    private static function body(KernelBrowser $client): array
    {
        return json_decode((string) $client->getResponse()->getContent(), true);
    }
}
