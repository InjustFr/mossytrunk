<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use App\Tests\Support\ActsAsUser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ProductApiTest extends WebTestCase
{
    use ActsAsUser;

    public function testCreateAndListProducts(): void
    {
        $client = self::signedInClient();

        $client->jsonRequest('POST', '/api/products', ['name' => 'T-shirt', 'sellingPrice' => 2_000, 'variants' => ['S', 'M']]);
        self::assertResponseStatusCodeSame(201);

        $client->jsonRequest('GET', '/api/products');
        self::assertResponseIsSuccessful();
        $products = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertSame('T-shirt', $products[0]['name']);
        self::assertSame(0, $products[0]['buyingPrice']);
        self::assertSame(['S', 'M'], $products[0]['variants']);
    }

    public function testInvalidPayloadReturnsViolations(): void
    {
        $client = self::signedInClient();

        $client->jsonRequest('POST', '/api/products', ['name' => '', 'sellingPrice' => -5]);

        self::assertResponseStatusCodeSame(422);
        $body = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertEqualsCanonicalizing(['name', 'sellingPrice'], array_column($body['violations'], 'propertyPath'));
    }

    public function testBusinessRuleViolationReturnsDetail(): void
    {
        $client = self::signedInClient();
        $client->jsonRequest('POST', '/api/products', ['name' => 'T-shirt', 'sellingPrice' => 2_000]);
        $client->jsonRequest('GET', '/api/products');
        $id = json_decode((string) $client->getResponse()->getContent(), true)[0]['id'];

        $client->jsonRequest('PUT', "/api/products/$id", ['name' => 'T-shirt', 'sellingPrice' => 2_000, 'variants' => ['S', 's ', 'S']]);

        self::assertResponseStatusCodeSame(422);
    }
}
