<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ProductApiTest extends WebTestCase
{
    public function testCreateAndListProducts(): void
    {
        $client = self::createClient();

        $client->jsonRequest('POST', '/api/products', ['reference' => 'TS-01', 'name' => 'T-shirt', 'sellingPrice' => 2_000, 'variants' => ['S', 'M']]);
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
        $client = self::createClient();

        $client->jsonRequest('POST', '/api/products', ['reference' => '', 'name' => 'T-shirt', 'sellingPrice' => -5]);

        self::assertResponseStatusCodeSame(422);
        $body = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertEqualsCanonicalizing(['reference', 'sellingPrice'], array_column($body['violations'], 'propertyPath'));
    }

    public function testBusinessRuleViolationReturnsDetail(): void
    {
        $client = self::createClient();
        $client->jsonRequest('POST', '/api/products', ['reference' => 'TS-01', 'name' => 'T-shirt', 'sellingPrice' => 2_000]);
        $client->jsonRequest('POST', '/api/products', ['reference' => 'TS-01', 'name' => 'Autre', 'sellingPrice' => 2_000]);

        self::assertResponseStatusCodeSame(422);
        self::assertStringContainsString('déjà utilisée', json_decode((string) $client->getResponse()->getContent(), true)['detail']);
    }
}
