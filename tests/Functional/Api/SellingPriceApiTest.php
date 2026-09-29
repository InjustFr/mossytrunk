<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use App\Tests\Support\SignsInClient;
use App\Tests\Support\Json;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class SellingPriceApiTest extends WebTestCase
{
    use SignsInClient;

    public function testFixAnImportedPriceThenRecordThePastOne(): void
    {
        $client = self::signedInClient();
        $client->jsonRequest('POST', '/api/products', ['name' => 'Sticker', 'sellingPrice' => 9_999]);
        $productId = Json::string(self::body($client), 'id');
        $importedId = Json::string(self::product($client, $productId), 'priceHistory', 0, 'id');

        $client->jsonRequest('PUT', "/api/products/$productId/prices/$importedId", ['price' => 450, 'since' => '2026-09-01']);
        self::assertResponseStatusCodeSame(204);
        $client->jsonRequest('POST', "/api/products/$productId/prices", ['price' => 400, 'since' => '2026-07-01']);
        self::assertResponseStatusCodeSame(204);

        $product = self::product($client, $productId);
        self::assertSame(450, Json::at($product, 'product', 'sellingPrice'));
        self::assertSame([['price' => 450, 'sinceDay' => '2026-09-01'], ['price' => 400, 'sinceDay' => '2026-07-01']], array_map(
            static fn (mixed $change): array => ['price' => Json::at($change, 'price'), 'sinceDay' => Json::at($change, 'sinceDay')],
            Json::array($product, 'priceHistory'),
        ));

        $client->jsonRequest('DELETE', '/api/products/'.$productId.'/prices/'.Json::string($product, 'priceHistory', 0, 'id'));
        self::assertSame(400, Json::at(self::product($client, $productId), 'product', 'sellingPrice'));

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
        return Json::decode((string) $client->getResponse()->getContent());
    }
}
