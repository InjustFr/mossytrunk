<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use App\Tests\Support\Json;
use App\Tests\Support\ProductTypesApi;
use App\Tests\Support\SignsInClient;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ProductTypeApiTest extends WebTestCase
{
    use SignsInClient;

    public function testCreateWithAColorThenEditNameAndColor(): void
    {
        $client = self::signedInClient();
        $client->jsonRequest('POST', '/api/product-types', ['name' => 'Print', 'color' => '#4f6d8f']);
        self::assertResponseStatusCodeSame(201);
        $created = Json::decode((string) $client->getResponse()->getContent());
        self::assertSame('#4f6d8f', Json::string($created, 'color'));

        $client->jsonRequest('PUT', '/api/product-types/'.Json::string($created, 'id'), ['name' => 'Affiche', 'color' => '#c29a2e']);
        self::assertResponseStatusCodeSame(204);

        $client->jsonRequest('GET', '/api/product-types');
        $type = Json::array(Json::decode((string) $client->getResponse()->getContent()), 0);
        self::assertSame(['Affiche', 'PRI', '#c29a2e'], [$type['name'], $type['code'], $type['color']]);
    }

    public function testCodeIsSuggestedThenChosen(): void
    {
        $client = self::signedInClient();
        $client->jsonRequest('POST', '/api/product-types', ['name' => 'Print']);

        $client->jsonRequest('GET', '/api/product-types/code-suggestion?name=Pringles');
        self::assertSame('PRI2', Json::string(Json::decode((string) $client->getResponse()->getContent()), 'code'));

        $client->jsonRequest('POST', '/api/product-types', ['name' => 'Pringles', 'code' => 'chips']);
        self::assertResponseStatusCodeSame(201);
        self::assertSame('CHIPS', Json::string(Json::decode((string) $client->getResponse()->getContent()), 'code'));

        $client->jsonRequest('POST', '/api/product-types', ['name' => 'Affiche', 'code' => 'PRI']);
        self::assertResponseStatusCodeSame(422);
    }

    public function testColorMustBeAHexColor(): void
    {
        $client = self::signedInClient();
        $client->jsonRequest('POST', '/api/product-types', ['name' => 'Print']);
        $id = Json::string(Json::decode((string) $client->getResponse()->getContent()), 'id');

        $client->jsonRequest('PUT', '/api/product-types/'.$id, ['name' => 'Print', 'color' => 'rouge']);

        self::assertResponseStatusCodeSame(422);
        $paths = array_column(Json::array(Json::decode((string) $client->getResponse()->getContent()), 'violations'), 'propertyPath');
        self::assertSame(['color'], $paths);
    }

    public function testCreateWithVariantsAndNamesNotPrefixedThenReorderAndPrefix(): void
    {
        $client = self::signedInClient();
        $client->jsonRequest('POST', '/api/product-types', ['name' => 'Print', 'variants' => ['A4', 'A3'], 'prefixesNames' => false]);
        self::assertResponseStatusCodeSame(201);
        $created = Json::decode((string) $client->getResponse()->getContent());
        self::assertSame([['A4', 'A3'], false], [Json::at($created, 'variants'), Json::at($created, 'prefixesNames')]);

        $client->jsonRequest('PUT', '/api/product-types/'.Json::string($created, 'id'), ['name' => 'Print', 'color' => '#5b7f3a', 'variants' => ['A5', 'A3', 'A4'], 'prefixesNames' => true]);
        self::assertResponseStatusCodeSame(204);

        self::assertSame([['A5', 'A3', 'A4'], true], [Json::at(self::types($client), 0, 'variants'), Json::at(self::types($client), 0, 'prefixesNames')]);
    }

    public function testATypeWithoutVariantsPrefixesNamesByDefault(): void
    {
        $client = self::signedInClient();
        $client->jsonRequest('POST', '/api/product-types', ['name' => 'Print']);

        $type = Json::array(self::types($client), 0);

        self::assertSame([[], true], [$type['variants'], $type['prefixesNames']]);
    }

    public function testAVariantUsedByAProductCannotBeDropped(): void
    {
        $client = self::signedInClient();
        $print = ProductTypesApi::create($client, 'Print', ['A4', 'A3']);
        $client->jsonRequest('POST', '/api/products', ['name' => 'Forêt', 'sellingPrice' => 1_500, 'typeId' => $print, 'variants' => ['A4']]);

        $client->jsonRequest('PUT', "/api/product-types/$print", ['name' => 'Print', 'color' => '#5b7f3a', 'variants' => ['A3']]);

        self::assertResponseStatusCodeSame(422);
        self::assertSame('The variant “A4” is still used by products, templates or discounts: remove it from them first.', Json::string(Json::decode((string) $client->getResponse()->getContent()), 'detail'));
        self::assertSame(['A4', 'A3'], Json::at(self::types($client), 0, 'variants'));
    }

    public function testVariantsMustNotBeBlank(): void
    {
        $client = self::signedInClient();

        $client->jsonRequest('POST', '/api/product-types', ['name' => 'Print', 'variants' => ['A4', '']]);
        self::assertResponseStatusCodeSame(422);
        self::assertSame(['variants[1]'], array_column(Json::array(Json::decode((string) $client->getResponse()->getContent()), 'violations'), 'propertyPath'));

        $client->jsonRequest('POST', '/api/product-types', ['name' => 'Print', 'variants' => ['A4', ' ']]);
        self::assertResponseStatusCodeSame(422);
        self::assertSame('A variant cannot be empty.', Json::string(Json::decode((string) $client->getResponse()->getContent()), 'detail'));
    }

    public function testRenameAVariantOfTheTypeAndItsProducts(): void
    {
        $client = self::signedInClient();
        $print = ProductTypesApi::create($client, 'Print', ['A4', 'A3']);
        $client->jsonRequest('POST', '/api/products', ['name' => 'Forêt', 'sellingPrice' => 1_500, 'typeId' => $print, 'variants' => ['A4', 'A3']]);

        $client->jsonRequest('POST', "/api/product-types/$print/variant-renaming", ['from' => 'A4', 'to' => 'Grand']);
        self::assertResponseStatusCodeSame(204);

        self::assertSame(['Grand', 'A3'], Json::at(self::types($client), 0, 'variants'));
        $client->jsonRequest('GET', '/api/products');
        self::assertSame(['Grand', 'A3'], Json::at(Json::decode((string) $client->getResponse()->getContent()), 0, 'variants'));
    }

    public function testAVariantCannotBeRenamedAsAnotherOne(): void
    {
        $client = self::signedInClient();
        $print = ProductTypesApi::create($client, 'Print', ['A4', 'A3']);

        $client->jsonRequest('POST', "/api/product-types/$print/variant-renaming", ['from' => 'A4', 'to' => 'a3'], ['HTTP_ACCEPT_LANGUAGE' => 'fr']);

        self::assertResponseStatusCodeSame(422);
        self::assertSame('La variante « a3 » existe déjà pour ce type.', Json::string(Json::decode((string) $client->getResponse()->getContent()), 'detail'));
    }

    public function testRenamingNeedsBothVariants(): void
    {
        $client = self::signedInClient();
        $print = ProductTypesApi::create($client, 'Print', ['A4']);

        $client->jsonRequest('POST', "/api/product-types/$print/variant-renaming", ['from' => '', 'to' => '']);

        self::assertResponseStatusCodeSame(422);
        self::assertSame(['from', 'to'], array_column(Json::array(Json::decode((string) $client->getResponse()->getContent()), 'violations'), 'propertyPath'));
    }

    /**
     * @return array<mixed>
     */
    private static function types(KernelBrowser $client): array
    {
        $client->jsonRequest('GET', '/api/product-types');

        return Json::decode((string) $client->getResponse()->getContent());
    }
}
