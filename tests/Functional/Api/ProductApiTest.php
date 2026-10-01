<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use App\Infrastructure\Security\SecurityUser;
use App\Tests\Support\Json;
use App\Tests\Support\ProductTypesApi;
use App\Tests\Support\SignsInClient;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ProductApiTest extends WebTestCase
{
    use SignsInClient;

    public function testCreateAndListProducts(): void
    {
        $client = self::signedInClient();
        $tshirt = ProductTypesApi::create($client, 'T-shirt', ['M', 'L'], prefixesNames: true);

        $client->jsonRequest('POST', '/api/products', ['name' => 'Mousse', 'sellingPrice' => 2_000, 'variants' => ['S', 'm'], 'typeId' => $tshirt]);
        self::assertResponseStatusCodeSame(201);

        $client->jsonRequest('GET', '/api/products');
        self::assertResponseIsSuccessful();
        $products = Json::decode((string) $client->getResponse()->getContent());
        self::assertSame('Mousse', Json::at($products, 0, 'name'));
        self::assertSame('T-shirt Mousse', Json::at($products, 0, 'displayName'));
        self::assertSame([$tshirt, 'T-shirt'], [Json::at($products, 0, 'typeId'), Json::at($products, 0, 'typeName')]);
        self::assertSame(0, Json::at($products, 0, 'buyingPrice'));
        self::assertSame(['S', 'M'], Json::at($products, 0, 'variants'));

        $client->jsonRequest('GET', '/api/product-types');
        self::assertSame(['M', 'L', 'S'], Json::at(Json::decode((string) $client->getResponse()->getContent()), 0, 'variants'));
    }

    public function testReferenceIsSuggestedThenChosen(): void
    {
        $client = self::signedInClient();
        $client->jsonRequest('POST', '/api/product-types', ['name' => 'Print']);
        $print = Json::string(Json::decode((string) $client->getResponse()->getContent()), 'id');
        $client->jsonRequest('POST', '/api/products', ['name' => 'Forêt', 'sellingPrice' => 1_500, 'typeId' => $print]);

        $client->jsonRequest('GET', '/api/products/reference-suggestion?'.http_build_query(['name' => 'Fougère', 'typeId' => $print]));
        self::assertSame('PRI-FOU', Json::string(Json::decode((string) $client->getResponse()->getContent()), 'reference'));
        $client->jsonRequest('GET', '/api/products/reference-suggestion?'.http_build_query(['name' => 'Forêt', 'typeId' => $print]));
        self::assertSame('PRI-FOR-2', Json::string(Json::decode((string) $client->getResponse()->getContent()), 'reference'));

        $client->jsonRequest('POST', '/api/products', ['name' => 'Fougère', 'sellingPrice' => 1_500, 'typeId' => $print, 'reference' => 'PRI-FOUGERE']);
        self::assertResponseStatusCodeSame(201);
        $id = Json::string(Json::decode((string) $client->getResponse()->getContent()), 'id');

        $client->jsonRequest('PUT', "/api/products/$id", ['name' => 'Fougère', 'sellingPrice' => 1_500, 'typeId' => $print, 'reference' => 'PRI-FOR']);
        self::assertResponseStatusCodeSame(422);
    }

    public function testDeleteSelectedProducts(): void
    {
        $client = self::signedInClient();
        $typeId = ProductTypesApi::create($client);
        $ids = [];
        foreach (['Sticker', 'Pin', 'Badge'] as $name) {
            $client->jsonRequest('POST', '/api/products', ['name' => $name, 'sellingPrice' => 400, 'typeId' => $typeId]);
            $ids[] = Json::string(Json::decode((string) $client->getResponse()->getContent()), 'id');
        }

        $client->jsonRequest('POST', '/api/products/deletion', ['productIds' => []]);
        self::assertResponseStatusCodeSame(422);
        $client->jsonRequest('POST', '/api/products/deletion', ['productIds' => [$ids[0], $ids[1]]]);
        self::assertResponseIsSuccessful();
        self::assertSame(['deleted' => 2], Json::decode((string) $client->getResponse()->getContent()));

        $client->jsonRequest('GET', '/api/products');
        $listed = $client->getResponse();
        $products = Json::decode((string) $listed->getContent());
        self::assertSame([$ids[2]], array_map(static fn (mixed $product): string => Json::string($product, 'id'), $products));
    }

    public function testMoveAProductIntoANewOneAsAVariant(): void
    {
        $client = self::signedInClient();
        $client->jsonRequest('POST', '/api/products', ['name' => 'Mug Lichen', 'sellingPrice' => 1_200, 'typeId' => ProductTypesApi::create($client)]);
        $id = Json::string(Json::decode((string) $client->getResponse()->getContent()), 'id');

        $client->jsonRequest('POST', "/api/products/{$id}/move-variant", ['targetVariant' => 'Lichen']);
        self::assertResponseStatusCodeSame(422);

        $client->jsonRequest('POST', "/api/products/{$id}/move-variant", ['newProductName' => 'Mug', 'targetVariant' => 'Lichen']);
        self::assertResponseIsSuccessful();
        $client->jsonRequest('GET', '/api/products');
        $products = Json::decode((string) $client->getResponse()->getContent());
        self::assertSame([['Mug', ['Lichen']]], array_map(static fn (mixed $product): array => [Json::at($product, 'name'), Json::at($product, 'variants')], $products));
    }

    public function testInvalidPayloadReturnsViolationsAndATypeIsRequired(): void
    {
        $client = self::signedInClient();

        $client->jsonRequest('POST', '/api/products', ['name' => '', 'sellingPrice' => -5]);

        self::assertResponseStatusCodeSame(422);
        $body = Json::decode((string) $client->getResponse()->getContent());
        self::assertEqualsCanonicalizing(['name', 'sellingPrice', 'typeId'], array_column(Json::array($body, 'violations'), 'propertyPath'));
    }

    public function testBusinessRuleViolationReturnsDetail(): void
    {
        $client = self::signedInClient();
        $typeId = ProductTypesApi::create($client);
        $client->jsonRequest('POST', '/api/products', ['name' => 'T-shirt', 'sellingPrice' => 2_000, 'typeId' => $typeId]);
        $client->jsonRequest('GET', '/api/products');
        $id = Json::string(Json::decode((string) $client->getResponse()->getContent()), 0, 'id');

        $client->jsonRequest('PUT', "/api/products/$id", ['name' => 'T-shirt', 'sellingPrice' => 2_000, 'typeId' => $typeId, 'variants' => ['S', 'S ']]);

        self::assertResponseStatusCodeSame(422);
        self::assertSame('The variant “S” already exists for this product.', Json::string(Json::decode((string) $client->getResponse()->getContent()), 'detail'));

        $client->jsonRequest('PUT', "/api/products/$id", ['name' => 'T-shirt', 'sellingPrice' => 2_000, 'typeId' => $typeId, 'variants' => ['S', 'S ']], ['HTTP_ACCEPT_LANGUAGE' => 'fr']);

        self::assertResponseStatusCodeSame(422);
        self::assertSame('La variante « S » existe déjà pour ce produit.', Json::string(Json::decode((string) $client->getResponse()->getContent()), 'detail'));
    }

    public function testProductsOfAnotherWorkspaceAreNotFound(): void
    {
        $client = self::signedInClient('Atelier A');
        $typeId = ProductTypesApi::create($client);
        $client->jsonRequest('POST', '/api/products', ['name' => 'T-shirt', 'sellingPrice' => 2_000, 'typeId' => $typeId]);
        $id = Json::string(Json::decode((string) $client->getResponse()->getContent()), 'id');

        $client->loginUser(SecurityUser::fromUser(self::createMember('Atelier B')));
        $client->jsonRequest('PUT', "/api/products/$id", ['name' => 'Volé', 'sellingPrice' => 1, 'typeId' => $typeId]);
        self::assertResponseStatusCodeSame(404);

        $client->jsonRequest('GET', '/api/products');
        self::assertSame([], Json::decode((string) $client->getResponse()->getContent()));
    }

    public function testChangingTheTypeInBatchNeedsAType(): void
    {
        $client = self::signedInClient();
        $client->jsonRequest('POST', '/api/products', ['name' => 'Mousse', 'sellingPrice' => 400, 'typeId' => ProductTypesApi::create($client)]);
        $id = Json::string(Json::decode((string) $client->getResponse()->getContent()), 'id');

        $client->jsonRequest('POST', '/api/products/batch', ['productIds' => [$id], 'changeType' => true]);
        self::assertResponseStatusCodeSame(422);
        self::assertSame([['typeId', 'Choose a type.']], array_map(
            static fn (mixed $violation): array => [Json::at($violation, 'propertyPath'), Json::at($violation, 'title')],
            Json::array(Json::decode((string) $client->getResponse()->getContent()), 'violations'),
        ));

        $sticker = ProductTypesApi::create($client, 'Sticker', prefixesNames: true);
        $client->jsonRequest('POST', '/api/products/batch', ['productIds' => [$id], 'changeType' => true, 'typeId' => $sticker]);
        self::assertResponseIsSuccessful();
        $client->jsonRequest('GET', '/api/products');
        self::assertSame('Sticker Mousse', Json::at(Json::decode((string) $client->getResponse()->getContent()), 0, 'displayName'));
    }

    public function testTheLowStockAlertIsSetInBatch(): void
    {
        $client = self::signedInClient();
        $client->jsonRequest('POST', '/api/products', ['name' => 'Mousse', 'sellingPrice' => 400, 'typeId' => ProductTypesApi::create($client)]);
        $id = Json::string(Json::decode((string) $client->getResponse()->getContent()), 'id');

        $client->jsonRequest('POST', '/api/products/batch', ['productIds' => [$id], 'lowStockThreshold' => -1]);
        self::assertResponseStatusCodeSame(422);

        $client->jsonRequest('POST', '/api/products/batch', ['productIds' => [$id], 'sellingPrice' => 500, 'priceSince' => 'hier']);
        self::assertResponseStatusCodeSame(422);
        $client->jsonRequest('POST', '/api/products/batch', ['productIds' => [$id], 'sellingPrice' => 500, 'priceSince' => '2999-01-01']);
        self::assertResponseStatusCodeSame(422);

        $client->jsonRequest('POST', '/api/products/batch', ['productIds' => [$id], 'lowStockThreshold' => 4]);
        self::assertResponseIsSuccessful();
        $client->jsonRequest('GET', '/api/products');
        self::assertSame(4, Json::at(Json::decode((string) $client->getResponse()->getContent()), 0, 'lowStockThreshold'));
    }

    public function testTheListShowsTheStockOfEachVariant(): void
    {
        $client = self::signedInClient();
        $client->jsonRequest('POST', '/api/products', ['name' => 'Forêt', 'sellingPrice' => 1_500, 'typeId' => ProductTypesApi::create($client, 'Print', ['A4', 'A3']), 'variants' => ['A4', 'A3'], 'lowStockThreshold' => 2]);
        $id = Json::string(Json::decode((string) $client->getResponse()->getContent()), 'id');
        $client->jsonRequest('POST', '/api/stock/restock', ['productId' => $id, 'variant' => 'A4', 'quantity' => 5, 'totalPaid' => 1_000]);

        $client->jsonRequest('GET', '/api/products');

        $product = Json::array(Json::decode((string) $client->getResponse()->getContent()), 0);
        self::assertSame([
            ['variant' => 'A4', 'onHand' => 5, 'low' => false, 'negative' => false],
            ['variant' => 'A3', 'onHand' => 0, 'low' => true, 'negative' => false],
        ], $product['stock']);
        self::assertSame([5, true, false], [$product['onHand'], $product['lowStock'], $product['negativeStock']]);
    }
}
