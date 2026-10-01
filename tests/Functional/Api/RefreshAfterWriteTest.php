<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use App\Tests\Support\Json;
use App\Tests\Support\ProductTypesApi;
use App\Tests\Support\SignsInClient;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class RefreshAfterWriteTest extends WebTestCase
{
    use SignsInClient;

    public function testAWriteAnswersWithTheRefreshedDataTheClientAskedFor(): void
    {
        $client = self::signedInClient();
        $type = ProductTypesApi::create($client);

        $client->jsonRequest('POST', '/api/products', ['name' => 'Zine', 'sellingPrice' => 1_000, 'typeId' => $type], ['HTTP_X_REFRESH' => '["/api/products","/login","/api/unknown"]']);

        self::assertResponseStatusCodeSame(200);
        self::assertResponseHeaderSame('X-Refreshed', '1');
        $payload = Json::decode((string) $client->getResponse()->getContent());
        self::assertSame(['/api/products'], array_keys(Json::array($payload, 'refreshed')));
        self::assertSame(Json::string($payload, 'data', 'id'), Json::string($payload, 'refreshed', '/api/products', 0, 'id'));
    }

    public function testAWriteWithoutContentAnswersNullData(): void
    {
        $client = self::signedInClient();
        $client->jsonRequest('POST', '/api/products', ['name' => 'Zine', 'sellingPrice' => 1_000, 'typeId' => ProductTypesApi::create($client)]);
        $product = Json::string(Json::decode((string) $client->getResponse()->getContent()), 'id');

        $client->jsonRequest('DELETE', "/api/products/$product", server: ['HTTP_X_REFRESH' => '["/api/products"]']);

        self::assertResponseStatusCodeSame(200);
        $payload = Json::decode((string) $client->getResponse()->getContent());
        self::assertNull($payload['data']);
        self::assertSame([], Json::array($payload, 'refreshed', '/api/products'));
    }

    public function testAWriteWithoutRefreshRequestIsLeftAsIs(): void
    {
        $client = self::signedInClient();
        $client->jsonRequest('POST', '/api/products', ['name' => 'Zine', 'sellingPrice' => 1_000, 'typeId' => ProductTypesApi::create($client)]);
        $product = Json::string(Json::decode((string) $client->getResponse()->getContent()), 'id');

        $client->jsonRequest('DELETE', "/api/products/$product");

        self::assertResponseStatusCodeSame(204);
        self::assertFalse($client->getResponse()->headers->has('X-Refreshed'));
    }
}
