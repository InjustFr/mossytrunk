<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use App\Tests\Support\Json;
use App\Tests\Support\SignsInClient;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ServicesApiTest extends WebTestCase
{
    use SignsInClient;

    public function testImportReport(): void
    {
        $client = self::signedInClient();
        $client->jsonRequest('POST', '/api/services', ['service' => 'sumup', 'fields' => ['merchant_code' => 'MCODE', 'api_key' => 'sup_sk_test']]);
        self::assertResponseStatusCodeSame(201);
        $client->jsonRequest('POST', '/api/events', ['name' => 'Salon', 'location' => 'Lyon', 'startDate' => '2030-03-14', 'endDate' => '2030-03-15']);

        $client->jsonRequest('POST', '/api/services/sumup/import');

        self::assertResponseIsSuccessful();
        $report = Json::decode((string) $client->getResponse()->getContent());
        self::assertSame(['sumup', 'SumUp', 2], [$report['service'], $report['label'], $report['ordersImported']]);
        self::assertSame(['2030-03-21', '2030-03-23'], $report['datesWithoutEvent']);
    }

    public function testTheListNeverExposesASecret(): void
    {
        $client = self::signedInClient();
        $client->jsonRequest('POST', '/api/services', ['service' => 'sumup', 'fields' => ['merchant_code' => 'mcode', 'api_key' => 'sup_sk_very_secret_9876']]);

        $client->jsonRequest('GET', '/api/services');

        self::assertResponseIsSuccessful();
        $content = (string) $client->getResponse()->getContent();
        self::assertStringNotContainsString('very_secret', $content);
        $sumUp = array_values(array_filter(Json::decode($content), static fn (mixed $service): bool => \is_array($service) && 'sumup' === ($service['key'] ?? null)))[0];
        self::assertSame(['value' => 'MCODE', 'configured' => true, 'hint' => null], Json::at($sumUp, 'connection', 'values', 'merchant_code'));
        self::assertSame(['value' => null, 'configured' => true, 'hint' => '••••9876'], Json::at($sumUp, 'connection', 'values', 'api_key'));
    }

    public function testInvalidFieldsReturnViolations(): void
    {
        $client = self::signedInClient();

        $client->jsonRequest('POST', '/api/services', ['service' => 'sumup', 'fields' => ['merchant_code' => 'not valid!']]);

        self::assertResponseStatusCodeSame(422);
        $paths = array_values(array_map(static fn (mixed $violation): string => Json::string($violation, 'propertyPath'), Json::array(Json::decode((string) $client->getResponse()->getContent()), 'violations')));
        self::assertSame(['[merchant_code]', '[api_key]'], $paths);
    }

    public function testOptionsCanBeChangedWithoutRetypingTheSecret(): void
    {
        $client = self::signedInClient();
        $client->jsonRequest('POST', '/api/services', ['service' => 'sumup', 'fields' => ['merchant_code' => 'MCODE', 'api_key' => 'sup_sk_test']]);

        $client->jsonRequest('PUT', '/api/services/sumup', ['fields' => ['merchant_code' => 'MCODE', 'api_key' => ''], 'salesContext' => 'online', 'unknownItems' => 'link_by_hand']);
        self::assertResponseStatusCodeSame(204);

        $client->jsonRequest('DELETE', '/api/services/sumup');
        self::assertResponseStatusCodeSame(204);
    }

    public function testAnUnknownServiceIsNotFound(): void
    {
        $client = self::signedInClient();

        $client->jsonRequest('POST', '/api/services', ['service' => 'paypal', 'fields' => []]);
        self::assertResponseStatusCodeSame(404);

        $client->jsonRequest('POST', '/api/services/sumup/import');
        self::assertResponseStatusCodeSame(422);
    }
}
