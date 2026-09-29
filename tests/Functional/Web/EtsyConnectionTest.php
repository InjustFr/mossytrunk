<?php

declare(strict_types=1);

namespace App\Tests\Functional\Web;

use App\Tests\Support\SignsInClient;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class EtsyConnectionTest extends WebTestCase
{
    use SignsInClient;

    public function testConnectingGoesThroughEtsyAndBackToTheSettings(): void
    {
        $client = self::signedInClient();
        $client->jsonRequest('PUT', '/api/etsy/settings', ['keystring' => 'keystring123', 'sharedSecret' => 'shared-secret']);
        self::assertResponseStatusCodeSame(204);

        $client->request('GET', '/parametres/etsy/connexion');
        $authorization = (string) $client->getResponse()->headers->get('Location');
        self::assertStringStartsWith('http://localhost/parametres/etsy/retour?code=fake-code&state=', $authorization);

        $client->request('GET', $authorization);
        self::assertResponseRedirects('/parametres?etsy=connecte');
    }

    public function testWithoutAppKeysTheConnectionIsUnavailable(): void
    {
        $client = self::signedInClient();

        $client->request('GET', '/parametres/etsy/connexion');

        self::assertResponseRedirects('/parametres?etsy=indisponible');
    }

    public function testACallbackWithAnotherStateIsRefused(): void
    {
        $client = self::signedInClient();
        $client->jsonRequest('PUT', '/api/etsy/settings', ['keystring' => 'keystring123', 'sharedSecret' => 'shared-secret']);
        $client->request('GET', '/parametres/etsy/connexion');

        $client->request('GET', '/parametres/etsy/retour?code=fake-code&state=forged');

        self::assertResponseRedirects('/parametres?etsy=refuse');
    }
}
