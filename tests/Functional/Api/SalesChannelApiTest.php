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
        self::assertSame(['id' => $etsy, 'name' => 'Etsy', 'service' => 'etsy', 'serviceLabel' => 'Etsy', 'kind' => 'online', 'main' => false, 'supplies' => [], 'costs' => []], Json::at($channels, 1));
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

    public function testAProductIsPricedOnOneChannelAtATime(): void
    {
        $client = self::signedInClient();
        $client->jsonRequest('POST', '/api/sales-channels', ['name' => 'Etsy']);
        $etsy = Json::string(Json::decode((string) $client->getResponse()->getContent()), 'id');
        $client->jsonRequest('GET', "/api/sales-channels/$etsy");
        self::assertSame(['id' => $etsy, 'name' => 'Etsy', 'service' => null, 'serviceLabel' => null, 'kind' => 'online', 'main' => false, 'supplies' => [], 'costs' => []], Json::decode((string) $client->getResponse()->getContent()));
        $client->jsonRequest('GET', '/api/sales-channels');
        $main = Json::string(Json::decode((string) $client->getResponse()->getContent()), 0, 'id');

        $client->jsonRequest('POST', '/api/products', ['name' => 'Forêt', 'sellingPrice' => 1_500, 'typeId' => ProductTypesApi::create($client)]);
        $foret = Json::string(Json::decode((string) $client->getResponse()->getContent()), 'id');

        $client->jsonRequest('PUT', "/api/products/$foret/channel-prices/$etsy", ['price' => -1]);
        self::assertResponseStatusCodeSame(422);
        $client->jsonRequest('PUT', "/api/products/$foret/channel-prices/$etsy", ['price' => 1_800]);
        self::assertResponseStatusCodeSame(204);
        $client->jsonRequest('PUT', "/api/products/$foret/channel-prices/$main", ['price' => 1_600]);
        self::assertResponseStatusCodeSame(204);
        $client->jsonRequest('GET', "/api/products/$foret");
        $product = Json::decode((string) $client->getResponse()->getContent());
        self::assertSame([1_600, [$etsy => 1_800]], [Json::at($product, 'product', 'sellingPrice'), Json::at($product, 'product', 'channelPrices')]);

        $client->jsonRequest('PUT', "/api/products/$foret/channel-prices/$etsy", ['price' => null]);
        $client->jsonRequest('GET', "/api/products/$foret");
        self::assertSame([], Json::at(Json::decode((string) $client->getResponse()->getContent()), 'product', 'channelPrices'));
    }

    public function testAChannelChargesItsCostsOnEachOrderAndAnOrderPaysItsPostage(): void
    {
        $client = self::signedInClient();
        $client->jsonRequest('GET', '/api/sales-channels');
        $main = Json::string(Json::decode((string) $client->getResponse()->getContent()), 0, 'id');
        $client->jsonRequest('POST', "/api/sales-channels/$main/costs", ['label' => 'Commission', 'kind' => 'percent', 'amount' => 175]);
        self::assertResponseStatusCodeSame(201);
        $commission = Json::string(Json::decode((string) $client->getResponse()->getContent()), 'id');
        $client->jsonRequest('POST', "/api/sales-channels/$main/costs", ['label' => ' ', 'kind' => 'fixed', 'amount' => 10]);
        self::assertResponseStatusCodeSame(422);
        $client->jsonRequest('POST', "/api/sales-channels/$main/costs", ['label' => 'Sac', 'kind' => 'gift', 'amount' => 10]);
        self::assertResponseStatusCodeSame(422);

        $client->jsonRequest('POST', '/api/events', ['name' => 'Japan Expo', 'location' => 'Villepinte', 'startDate' => '2026-07-09', 'endDate' => '2026-07-12']);
        $client->jsonRequest('POST', '/api/products', ['name' => 'Forêt', 'sellingPrice' => 2_000, 'typeId' => ProductTypesApi::create($client)]);
        $product = Json::string(Json::decode((string) $client->getResponse()->getContent()), 'id');
        $client->jsonRequest('POST', '/api/orders', ['placedAt' => '2026-07-10T15:30', 'lines' => [['productId' => $product, 'variant' => null, 'quantity' => 1]]]);
        $order = Json::string(Json::decode((string) $client->getResponse()->getContent()), 'id');

        $client->jsonRequest('PUT', "/api/sales-channels/$main/costs/$commission", ['label' => 'Commission SumUp', 'kind' => 'percent', 'amount' => 200]);
        self::assertResponseStatusCodeSame(204);
        $client->jsonRequest('PUT', "/api/orders/$order/postage", ['postage' => 150]);
        self::assertResponseStatusCodeSame(204);
        $client->jsonRequest('GET', "/api/orders/$order");
        $view = Json::decode((string) $client->getResponse()->getContent());
        self::assertSame([[['label' => 'Commission', 'amount' => 35]], 150, 185], [Json::at($view, 'charges'), Json::at($view, 'postage'), Json::at($view, 'channelCosts')]);

        $client->jsonRequest('POST', '/api/orders/charges', ['orderIds' => [$order]]);
        self::assertSame(['updated' => 1], Json::decode((string) $client->getResponse()->getContent()));
        $client->jsonRequest('GET', "/api/orders/$order");
        self::assertSame([['label' => 'Commission SumUp', 'amount' => 40]], Json::at(Json::decode((string) $client->getResponse()->getContent()), 'charges'));

        $client->jsonRequest('GET', "/api/sales-channels/$main");
        self::assertSame([['id' => $commission, 'label' => 'Commission SumUp', 'kind' => 'percent', 'amount' => 200]], Json::at(Json::decode((string) $client->getResponse()->getContent()), 'costs'));
        $client->jsonRequest('DELETE', "/api/sales-channels/$main/costs/$commission");
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
