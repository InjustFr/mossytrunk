<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use App\Infrastructure\Security\SecurityUser;
use App\Tests\Support\Json;
use App\Tests\Support\SignsInClient;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ProductApiTest extends WebTestCase
{
    use SignsInClient;

    public function testCreateAndListProducts(): void
    {
        $client = self::signedInClient();

        $client->jsonRequest('POST', '/api/products', ['name' => 'T-shirt', 'sellingPrice' => 2_000, 'variants' => ['S', 'M']]);
        self::assertResponseStatusCodeSame(201);

        $client->jsonRequest('GET', '/api/products');
        self::assertResponseIsSuccessful();
        $products = Json::decode((string) $client->getResponse()->getContent());
        self::assertSame('T-shirt', Json::at($products, 0, 'name'));
        self::assertSame(0, Json::at($products, 0, 'buyingPrice'));
        self::assertSame(['S', 'M'], Json::at($products, 0, 'variants'));
    }

    public function testDeleteAllProducts(): void
    {
        $client = self::signedInClient();
        $client->jsonRequest('POST', '/api/products', ['name' => 'Sticker', 'sellingPrice' => 400]);
        $client->jsonRequest('POST', '/api/products', ['name' => 'Pin', 'sellingPrice' => 400]);

        $client->jsonRequest('DELETE', '/api/products');
        self::assertResponseIsSuccessful();
        self::assertSame(['deleted' => 2], Json::decode((string) $client->getResponse()->getContent()));

        $client->jsonRequest('GET', '/api/products');
        self::assertSame([], Json::decode((string) $client->getResponse()->getContent()));
    }

    public function testMoveAProductIntoANewOneAsAVariant(): void
    {
        $client = self::signedInClient();
        $client->jsonRequest('POST', '/api/products', ['name' => 'Mug Lichen', 'sellingPrice' => 1_200]);
        $id = Json::string(Json::decode((string) $client->getResponse()->getContent()), 'id');

        $client->jsonRequest('POST', "/api/products/{$id}/move-variant", ['targetVariant' => 'Lichen']);
        self::assertResponseStatusCodeSame(422);

        $client->jsonRequest('POST', "/api/products/{$id}/move-variant", ['newProductName' => 'Mug', 'targetVariant' => 'Lichen']);
        self::assertResponseIsSuccessful();
        $client->jsonRequest('GET', '/api/products');
        $products = Json::decode((string) $client->getResponse()->getContent());
        self::assertSame([['Mug', ['Lichen']]], array_map(static fn (mixed $product): array => [Json::at($product, 'name'), Json::at($product, 'variants')], $products));
    }

    public function testInvalidPayloadReturnsViolations(): void
    {
        $client = self::signedInClient();

        $client->jsonRequest('POST', '/api/products', ['name' => '', 'sellingPrice' => -5]);

        self::assertResponseStatusCodeSame(422);
        $body = Json::decode((string) $client->getResponse()->getContent());
        self::assertEqualsCanonicalizing(['name', 'sellingPrice'], array_column(Json::array($body, 'violations'), 'propertyPath'));
    }

    public function testBusinessRuleViolationReturnsDetail(): void
    {
        $client = self::signedInClient();
        $client->jsonRequest('POST', '/api/products', ['name' => 'T-shirt', 'sellingPrice' => 2_000]);
        $client->jsonRequest('GET', '/api/products');
        $id = Json::string(Json::decode((string) $client->getResponse()->getContent()), 0, 'id');

        $client->jsonRequest('PUT', "/api/products/$id", ['name' => 'T-shirt', 'sellingPrice' => 2_000, 'variants' => ['S', 's ', 'S']]);

        self::assertResponseStatusCodeSame(422);
    }

    public function testProductsOfAnotherWorkspaceAreNotFound(): void
    {
        $client = self::signedInClient('Atelier A');
        $client->jsonRequest('POST', '/api/products', ['name' => 'T-shirt', 'sellingPrice' => 2_000]);
        $id = Json::string(Json::decode((string) $client->getResponse()->getContent()), 'id');

        $client->loginUser(SecurityUser::fromUser(self::createMember('Atelier B')));
        $client->jsonRequest('PUT', "/api/products/$id", ['name' => 'Volé', 'sellingPrice' => 1]);
        self::assertResponseStatusCodeSame(404);

        $client->jsonRequest('GET', '/api/products');
        self::assertSame([], Json::decode((string) $client->getResponse()->getContent()));
    }
}
