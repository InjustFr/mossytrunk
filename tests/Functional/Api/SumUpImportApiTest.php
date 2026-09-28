<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use App\Tests\Support\ActsAsUser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class SumUpImportApiTest extends WebTestCase
{
    use ActsAsUser;

    public function testImportReport(): void
    {
        $client = self::signedInClient();
        $client->jsonRequest('POST', '/api/events', ['name' => 'Salon', 'location' => 'Lyon', 'startDate' => '2030-03-14', 'endDate' => '2030-03-15']);

        $client->jsonRequest('POST', '/api/sumup/import');

        self::assertResponseIsSuccessful();
        $report = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertSame(2, $report['ordersImported']);
        self::assertSame(['2030-03-21', '2030-03-23'], $report['datesWithoutEvent']);
    }
}
