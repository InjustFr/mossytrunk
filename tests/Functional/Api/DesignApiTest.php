<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use App\Tests\Support\Json;
use App\Tests\Support\SignsInClient;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class DesignApiTest extends WebTestCase
{
    use SignsInClient;

    public function testFromGabaritToProduct(): void
    {
        $client = self::signedInClient();
        $client->jsonRequest('POST', '/api/gabarits', ['name' => 'Carte postale', 'sellingPrice' => 250, 'adaptations' => ['Marges 5 mm']]);
        self::assertResponseStatusCodeSame(201);
        $gabaritId = Json::string(self::body($client), 'id');

        $client->jsonRequest('POST', '/api/designs', ['name' => 'Clairière']);
        $designId = Json::string(self::body($client), 'id');
        $client->jsonRequest('POST', "/api/designs/$designId/declinations", ['gabaritId' => $gabaritId]);
        $declinationId = Json::string(self::body($client), 'id');

        $client->jsonRequest('POST', "/api/designs/$designId/validation");
        self::assertResponseStatusCodeSame(422);

        $client->jsonRequest('PUT', "/api/designs/$designId/declinations/$declinationId/adaptations", ['adaptation' => 'Marges 5 mm', 'done' => true]);
        self::assertResponseStatusCodeSame(204);
        $client->jsonRequest('POST', "/api/designs/$designId/validation");
        self::assertSame(1, self::body($client)['productsCreated']);

        $client->jsonRequest('GET', '/api/products');
        self::assertSame('Clairière', Json::at(self::body($client), 0, 'name'));

        $client->jsonRequest('GET', '/api/designs');
        self::assertSame('validated', Json::at(self::body($client), 'standalone', 0, 'status'));
    }

    public function testGabaritNeedsAName(): void
    {
        $client = self::signedInClient();

        $client->jsonRequest('POST', '/api/gabarits', ['name' => '', 'sellingPrice' => -1]);

        self::assertResponseStatusCodeSame(422);
        self::assertSame(['name', 'sellingPrice'], array_column(Json::array(self::body($client), 'violations'), 'propertyPath'));
    }

    /**
     * @return array<mixed>
     */
    private static function body(KernelBrowser $client): array
    {
        return Json::decode((string) $client->getResponse()->getContent());
    }
}
