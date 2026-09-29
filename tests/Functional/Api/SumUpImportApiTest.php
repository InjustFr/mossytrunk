<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use App\Tests\Support\SignsInClient;
use App\Tests\Support\Json;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class SumUpImportApiTest extends WebTestCase
{
    use SignsInClient;

    public function testImportReport(): void
    {
        $client = self::signedInClient();
        $client->jsonRequest('PUT', '/api/workspace/settings/sumup', ['merchantCode' => 'MCODE', 'apiKey' => 'sup_sk_test']);
        self::assertResponseStatusCodeSame(204);
        $client->jsonRequest('POST', '/api/events', ['name' => 'Salon', 'location' => 'Lyon', 'startDate' => '2030-03-14', 'endDate' => '2030-03-15']);

        $client->jsonRequest('POST', '/api/sumup/import');

        self::assertResponseIsSuccessful();
        $report = Json::decode((string) $client->getResponse()->getContent());
        self::assertSame(2, $report['ordersImported']);
        self::assertSame(['2030-03-21', '2030-03-23'], $report['datesWithoutEvent']);
    }

    public function testSettingsNeverExposeTheApiKey(): void
    {
        $client = self::signedInClient();
        $client->jsonRequest('PUT', '/api/workspace/settings/sumup', ['merchantCode' => 'MCODE', 'apiKey' => 'sup_sk_very_secret_9876']);

        $client->jsonRequest('GET', '/api/workspace/settings');

        self::assertResponseIsSuccessful();
        self::assertStringNotContainsString('very_secret', (string) $client->getResponse()->getContent());
        $settings = Json::decode((string) $client->getResponse()->getContent());
        self::assertSame(['merchantCode' => 'MCODE', 'apiKeyConfigured' => true, 'apiKeyHint' => '••••9876'], $settings['sumUp']);
    }

    public function testInvalidSettingsReturnViolations(): void
    {
        $client = self::signedInClient();

        $client->jsonRequest('PUT', '/api/workspace/settings/sumup', ['merchantCode' => 'not valid!']);

        self::assertResponseStatusCodeSame(422);
    }
}
