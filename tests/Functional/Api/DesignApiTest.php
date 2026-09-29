<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use App\Tests\Support\ActsAsUser;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class DesignApiTest extends WebTestCase
{
    use ActsAsUser;

    public function testFromGabaritToProduct(): void
    {
        $client = self::signedInClient();
        $client->jsonRequest('POST', '/api/gabarits', ['name' => 'Carte postale', 'sellingPrice' => 250, 'buyingPrice' => 40, 'adaptations' => ['Marges 5 mm']]);
        self::assertResponseStatusCodeSame(201);
        $gabaritId = self::body($client)['id'];

        $client->jsonRequest('POST', '/api/designs', ['name' => 'Clairière']);
        $designId = self::body($client)['id'];
        $client->jsonRequest('POST', "/api/designs/$designId/declinations", ['gabaritId' => $gabaritId]);
        $declinationId = self::body($client)['id'];

        $client->jsonRequest('POST', "/api/designs/$designId/validation");
        self::assertResponseStatusCodeSame(422);

        $client->jsonRequest('PUT', "/api/designs/$designId/declinations/$declinationId/adaptations", ['adaptation' => 'Marges 5 mm', 'done' => true]);
        self::assertResponseStatusCodeSame(204);
        $client->jsonRequest('POST', "/api/designs/$designId/validation");
        self::assertSame(['productsCreated' => 1], self::body($client));

        $client->jsonRequest('GET', '/api/products');
        self::assertSame('Clairière', self::body($client)[0]['name']);

        $client->jsonRequest('GET', '/api/designs');
        self::assertSame('validated', self::body($client)['standalone'][0]['status']);
    }

    public function testGabaritNeedsAName(): void
    {
        $client = self::signedInClient();

        $client->jsonRequest('POST', '/api/gabarits', ['name' => '', 'sellingPrice' => -1]);

        self::assertResponseStatusCodeSame(422);
        self::assertSame(['name', 'sellingPrice'], array_column(self::body($client)['violations'], 'propertyPath'));
    }

    /**
     * @return array<mixed>
     */
    private static function body(KernelBrowser $client): array
    {
        return json_decode((string) $client->getResponse()->getContent(), true);
    }
}
