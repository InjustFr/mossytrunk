<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use App\Tests\Support\Json;
use App\Tests\Support\ProductTypesApi;
use App\Tests\Support\SignsInClient;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class SalesChannelApiTest extends WebTestCase
{
    use SignsInClient;

    public function testChannelsPriceProductsOneByOneOrInBatch(): void
    {
        $client = self::signedInClient();
        $client->jsonRequest('POST', '/api/sales-channels', ['name' => 'Etsy', 'service' => 'etsy']);
        self::assertResponseStatusCodeSame(201);
        $etsy = Json::string(Json::decode((string) $client->getResponse()->getContent()), 'id');
        $client->jsonRequest('GET', '/api/sales-channels');
        $channels = Json::decode((string) $client->getResponse()->getContent());
        self::assertSame(['id' => $etsy, 'name' => 'Etsy', 'service' => 'etsy', 'serviceLabel' => 'Etsy', 'kind' => 'online', 'main' => false], Json::at($channels, 1));
        self::assertSame(['Marchés', 'market', true], [Json::at($channels, 0, 'name'), Json::at($channels, 0, 'kind'), Json::at($channels, 0, 'main')]);

        $type = ProductTypesApi::create($client);
        $client->jsonRequest('POST', '/api/products', ['name' => 'Forêt', 'sellingPrice' => 1_500, 'typeId' => $type, 'channelPrices' => [['channelId' => $etsy, 'price' => 1_800]]]);
        $foret = Json::string(Json::decode((string) $client->getResponse()->getContent()), 'id');
        $client->jsonRequest('POST', '/api/products', ['name' => 'Mousse', 'sellingPrice' => 400, 'typeId' => $type]);
        $mousse = Json::string(Json::decode((string) $client->getResponse()->getContent()), 'id');

        $client->jsonRequest('POST', '/api/products/batch', ['productIds' => [$mousse], 'channelPrice' => ['channelId' => $etsy, 'mode' => 'fixed']]);
        self::assertResponseStatusCodeSame(422);

        $client->jsonRequest('POST', '/api/products/batch', ['productIds' => [$mousse], 'channelPrice' => ['channelId' => $etsy, 'mode' => 'derived', 'adjustment' => 12.5, 'adjustmentUnit' => 'percent']]);
        self::assertResponseIsSuccessful();

        $client->jsonRequest('GET', '/api/products');
        $prices = [];
        foreach (Json::decode((string) $client->getResponse()->getContent()) as $product) {
            $prices[Json::string($product, 'id')] = Json::at($product, 'channelPrices');
        }
        self::assertSame([$foret => [$etsy => 1_800], $mousse => [$etsy => 450]], $prices);

        $client->jsonRequest('PUT', "/api/products/$foret", ['name' => 'Forêt', 'sellingPrice' => 1_500, 'typeId' => $type, 'channelPrices' => [['channelId' => $etsy, 'price' => null]]]);
        self::assertResponseIsSuccessful();
        $client->request('DELETE', "/api/sales-channels/$etsy");
        self::assertResponseStatusCodeSame(204);
    }

    public function testAChannelNeedsANameAndAFreeService(): void
    {
        $client = self::signedInClient();
        $client->jsonRequest('POST', '/api/sales-channels', ['name' => ' ']);
        self::assertResponseStatusCodeSame(422);

        $client->jsonRequest('POST', '/api/sales-channels', ['name' => 'Etsy', 'service' => 'etsy']);
        $client->jsonRequest('POST', '/api/sales-channels', ['name' => 'Autre', 'service' => 'etsy']);
        self::assertResponseStatusCodeSame(422);
    }
}
