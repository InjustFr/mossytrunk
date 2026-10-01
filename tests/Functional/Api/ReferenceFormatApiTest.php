<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use App\Tests\Support\Json;
use App\Tests\Support\ProductTypesApi;
use App\Tests\Support\SignsInClient;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ReferenceFormatApiTest extends WebTestCase
{
    use SignsInClient;

    public function testChooseAFormatThenApplyItToExistingProducts(): void
    {
        $client = self::signedInClient();
        $typeId = ProductTypesApi::create($client);
        $client->jsonRequest('POST', '/api/products', ['name' => 'Sticker', 'sellingPrice' => 400, 'typeId' => $typeId]);
        self::assertResponseStatusCodeSame(201);

        $client->jsonRequest('GET', '/api/references/formats');
        self::assertResponseIsSuccessful();
        self::assertSame(['kind' => 'product', 'template' => '{type}-{name}', 'existing' => 1], array_intersect_key(Json::array(self::body($client), 2), array_flip(['kind', 'template', 'existing'])));

        $client->jsonRequest('GET', '/api/references/formats/product/preview?template='.rawurlencode('REF-{number:5}'));
        self::assertSame('REF-00001', Json::string(self::body($client), 'example'));

        $client->jsonRequest('PUT', '/api/references/formats/product', ['template' => 'REF-{number:5}', 'applyToExisting' => true]);
        self::assertResponseIsSuccessful();
        self::assertSame(1, Json::int(self::body($client), 'renamed'));

        $client->jsonRequest('GET', '/api/products');
        self::assertSame('REF-00001', Json::string(self::body($client), 0, 'reference'));
        $client->jsonRequest('GET', '/api/products/reference-suggestion?name=Rivi%C3%A8re');
        self::assertSame('REF-00002', Json::string(self::body($client), 'reference'));
    }

    public function testAnInvalidTemplateIsExplained(): void
    {
        $client = self::signedInClient();

        $client->jsonRequest('GET', '/api/references/formats/supplier_order/preview?template='.rawurlencode('CMF-{time}'));
        self::assertResponseStatusCodeSame(422);
        self::assertSame('"{time}" is not a tag available here.', Json::string(self::body($client), 'detail'));

        $client->jsonRequest('PUT', '/api/references/formats/order', ['template' => 'CMD {number}', 'applyToExisting' => false]);
        self::assertResponseStatusCodeSame(422);
        self::assertStringStartsWith('" " is not allowed in a reference', Json::string(self::body($client), 'detail'));
    }

    public function testAnUnknownKindIsNotFound(): void
    {
        $client = self::signedInClient();

        $client->jsonRequest('PUT', '/api/references/formats/invoice', ['template' => 'F{number}']);

        self::assertResponseStatusCodeSame(404);
    }

    /**
     * @return array<mixed>
     */
    private static function body(KernelBrowser $client): array
    {
        return Json::decode((string) $client->getResponse()->getContent());
    }
}
