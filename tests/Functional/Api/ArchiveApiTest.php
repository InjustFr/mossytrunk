<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use App\Tests\Support\Json;
use App\Tests\Support\ProductTypesApi;
use App\Tests\Support\SignsInClient;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ArchiveApiTest extends WebTestCase
{
    use SignsInClient;

    public function testAProductIsArchivedThenRestored(): void
    {
        $client = self::signedInClient();
        $client->jsonRequest('POST', '/api/products', ['name' => 'Mousse', 'sellingPrice' => 400, 'typeId' => ProductTypesApi::create($client)]);
        $id = Json::string(self::body($client), 'id');

        $client->jsonRequest('PUT', "/api/products/$id/archive");
        self::assertResponseStatusCodeSame(204);
        self::assertSame([true, true], self::archiveState($client));

        $client->jsonRequest('DELETE', "/api/products/$id/archive");
        self::assertResponseStatusCodeSame(204);
        self::assertSame([false, false], self::archiveState($client));
    }

    public function testArchivingATypeArchivesItsProductsAndItsVariantsCanBeArchived(): void
    {
        $client = self::signedInClient();
        $print = ProductTypesApi::create($client, 'Print', ['A5', 'A4']);
        $client->jsonRequest('POST', '/api/products', ['name' => 'Forêt', 'sellingPrice' => 1_500, 'variants' => ['A5', 'A4'], 'typeId' => $print]);

        $client->jsonRequest('PUT', "/api/product-types/$print", ['name' => 'Print', 'color' => '#4f6d8f', 'archivedVariants' => ['a5']]);
        self::assertResponseStatusCodeSame(204);
        $client->jsonRequest('GET', '/api/products');
        self::assertSame(['A4'], Json::array(self::body($client), 0, 'activeVariants'));

        $client->jsonRequest('PUT', "/api/product-types/$print/archive");
        self::assertResponseStatusCodeSame(204);
        $client->jsonRequest('GET', '/api/product-types');
        self::assertSame([true, ['A5']], [Json::at(self::body($client), 0, 'archived'), Json::array(self::body($client), 0, 'archivedVariants')]);
        self::assertSame([true, false], self::archiveState($client));
    }

    public function testATypeIsDeletedOnlyWhenNothingUsesIt(): void
    {
        $client = self::signedInClient();
        $print = ProductTypesApi::create($client, 'Print');
        $sticker = ProductTypesApi::create($client, 'Sticker');
        $client->jsonRequest('POST', '/api/products', ['name' => 'Mousse', 'sellingPrice' => 400, 'typeId' => $sticker]);

        $client->jsonRequest('DELETE', "/api/product-types/$sticker");
        self::assertResponseStatusCodeSame(422);

        $client->jsonRequest('DELETE', "/api/product-types/$print");
        self::assertResponseStatusCodeSame(204);
        $client->jsonRequest('GET', '/api/product-types');
        self::assertSame(['Sticker'], array_column(self::body($client), 'name'));
    }

    /**
     * @return array{mixed, mixed}
     */
    private static function archiveState(KernelBrowser $client): array
    {
        $client->jsonRequest('GET', '/api/products');

        return [Json::at(self::body($client), 0, 'archived'), Json::at(self::body($client), 0, 'archivedItself')];
    }

    /**
     * @return array<mixed>
     */
    private static function body(KernelBrowser $client): array
    {
        return Json::decode((string) $client->getResponse()->getContent());
    }
}
