<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class EventApiTest extends WebTestCase
{
    public function testEventLifecycle(): void
    {
        $client = self::createClient();

        $client->jsonRequest('POST', '/api/events', ['name' => 'Japan Expo', 'location' => 'Villepinte', 'startDate' => '2026-07-09', 'endDate' => '2026-07-12']);
        self::assertResponseStatusCodeSame(201);
        $id = json_decode((string) $client->getResponse()->getContent(), true)['id'];

        $client->jsonRequest('POST', "/api/events/$id/expenses", ['label' => 'Stand', 'amount' => 30_000]);
        self::assertResponseStatusCodeSame(201);

        $client->jsonRequest('GET', "/api/events/$id");
        $event = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertSame('Villepinte', $event['location']);
        self::assertSame(30_000, $event['expensesTotal']);
    }

    public function testEndBeforeStartIsRejected(): void
    {
        $client = self::createClient();

        $client->jsonRequest('POST', '/api/events', ['name' => 'Japan Expo', 'location' => 'Villepinte', 'startDate' => '2026-07-12', 'endDate' => '2026-07-09']);

        self::assertResponseStatusCodeSame(422);
    }

    public function testUnknownEventIs404(): void
    {
        $client = self::createClient();

        $client->jsonRequest('GET', '/api/events/01K00000000000000000000000');

        self::assertResponseStatusCodeSame(404);
    }
}
