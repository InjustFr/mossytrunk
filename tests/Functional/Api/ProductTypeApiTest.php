<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use App\Tests\Support\Json;
use App\Tests\Support\SignsInClient;
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
}
