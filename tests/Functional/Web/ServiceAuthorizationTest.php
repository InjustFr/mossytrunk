<?php

declare(strict_types=1);

namespace App\Tests\Functional\Web;

use App\Tests\Support\SignsInClient;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ServiceAuthorizationTest extends WebTestCase
{
    use SignsInClient;

    public function testConnectingGoesThroughEtsyAndBackToTheSettings(): void
    {
        $client = self::signedInClient();
        self::addEtsy($client);

        $client->request('GET', '/parametres/etsy/connexion');
        $authorization = (string) $client->getResponse()->headers->get('Location');
        self::assertStringStartsWith('http://localhost/parametres/etsy/retour?code=fake-code&state=', $authorization);

        $client->request('GET', $authorization);
        self::assertResponseRedirects('/parametres?service=etsy&connexion=connecte');
    }

    public function testWithoutEtsyAddedTheConnectionIsUnavailable(): void
    {
        $client = self::signedInClient();

        $client->request('GET', '/parametres/etsy/connexion');

        self::assertResponseRedirects('/parametres?service=etsy&connexion=indisponible');
    }

    public function testAServiceWithoutAuthorizationCannotBeConnected(): void
    {
        $client = self::signedInClient();
        $client->jsonRequest('POST', '/api/services', ['service' => 'sumup', 'fields' => ['merchant_code' => 'MCODE', 'api_key' => 'sup_sk_test']]);

        $client->request('GET', '/parametres/sumup/connexion');

        self::assertResponseRedirects('/parametres?service=sumup&connexion=indisponible');
    }

    public function testACallbackWithAnotherStateIsRefused(): void
    {
        $client = self::signedInClient();
        self::addEtsy($client);
        $client->request('GET', '/parametres/etsy/connexion');

        $client->request('GET', '/parametres/etsy/retour?code=fake-code&state=forged');

        self::assertResponseRedirects('/parametres?service=etsy&connexion=refuse');
    }

    private static function addEtsy(KernelBrowser $client): void
    {
        $client->jsonRequest('POST', '/api/services', ['service' => 'etsy', 'fields' => ['keystring' => 'keystring123', 'shared_secret' => 'shared-secret']]);
        self::assertResponseStatusCodeSame(201);
    }
}
