<?php

declare(strict_types=1);

namespace App\Tests\Functional\Web;

use App\Tests\Support\SignsInClient;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ServerTimingTest extends WebTestCase
{
    use SignsInClient;

    public function testResponsesTellHowLongTheServerWorkedOnThem(): void
    {
        $client = self::signedInClient();

        $client->jsonRequest('GET', '/api/products');

        self::assertMatchesRegularExpression('/^app;dur=\d+\.\d$/', (string) $client->getResponse()->headers->get('Server-Timing'));
    }
}
