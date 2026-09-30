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

        $client->request('GET', '/settings/etsy/connect');
        $authorization = (string) $client->getResponse()->headers->get('Location');
        self::assertStringStartsWith('http://localhost/settings/etsy/callback?code=fake-code&state=', $authorization);

        $client->request('GET', $authorization);
        self::assertResponseRedirects('/settings?service=etsy&connection=connected');
    }

    public function testWithoutEtsyAddedTheConnectionIsUnavailable(): void
    {
        $client = self::signedInClient();

        $client->request('GET', '/settings/etsy/connect');

        self::assertResponseRedirects('/settings?service=etsy&connection=unavailable');
    }

    public function testAServiceWithoutAuthorizationCannotBeConnected(): void
    {
        $client = self::signedInClient();
        $client->jsonRequest('POST', '/api/services', ['service' => 'sumup', 'fields' => ['merchant_code' => 'MCODE', 'api_key' => 'sup_sk_test']]);

        $client->request('GET', '/settings/sumup/connect');

        self::assertResponseRedirects('/settings?service=sumup&connection=unavailable');
    }

    public function testACallbackWithAnotherStateIsRefused(): void
    {
        $client = self::signedInClient();
        self::addEtsy($client);
        $client->request('GET', '/settings/etsy/connect');

        $client->request('GET', '/settings/etsy/callback?code=fake-code&state=forged');

        self::assertResponseRedirects('/settings?service=etsy&connection=refused');
    }

    private static function addEtsy(KernelBrowser $client): void
    {
        $client->jsonRequest('POST', '/api/services', ['service' => 'etsy', 'fields' => ['keystring' => 'keystring123', 'shared_secret' => 'shared-secret']]);
        self::assertResponseStatusCodeSame(201);
    }
}
