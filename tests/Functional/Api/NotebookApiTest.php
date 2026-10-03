<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use App\Infrastructure\Security\SecurityUser;
use App\Tests\Support\Json;
use App\Tests\Support\ProductTypesApi;
use App\Tests\Support\SignsInClient;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class NotebookApiTest extends WebTestCase
{
    use SignsInClient;

    public function testTheScannedNotebookIsComparedOrderByOrderWithTheEventOrders(): void
    {
        $client = self::signedInClient();
        $type = ProductTypesApi::create($client);
        $lichen = self::created($client, '/api/products', ['name' => 'Carnet Lichen', 'sellingPrice' => 1_200, 'typeId' => $type]);
        $fern = self::created($client, '/api/products', ['name' => 'Carnet Fougère', 'sellingPrice' => 1_200, 'typeId' => $type]);
        $eventId = self::created($client, '/api/events', ['name' => 'Japan Expo', 'location' => 'Villepinte', 'startDate' => '2026-07-09', 'endDate' => '2026-07-12']);
        $matching = self::created($client, '/api/orders', ['placedAt' => '2026-07-10T10:00', 'lines' => [['productId' => $lichen, 'quantity' => 2]]]);
        $differing = self::created($client, '/api/orders', ['placedAt' => '2026-07-10T11:00', 'lines' => [['productId' => $lichen, 'quantity' => 1], ['productId' => $fern, 'quantity' => 2]]]);
        $notNoted = self::created($client, '/api/orders', ['placedAt' => '2026-07-10T12:00', 'lines' => [['productId' => $lichen, 'quantity' => 5]]]);

        $client->jsonRequest('GET', "/api/events/$eventId/notebook");
        self::assertSame('null', $client->getResponse()->getContent());

        $client->request('POST', '/api/notebook/recognitions', files: ['photo' => self::photo()]);
        self::assertResponseIsSuccessful();
        $text = Json::string(self::body($client), 'text');
        self::assertSame("Salon du dimanche\n1) 2 lichen\n2) fougère 12€ CB\n3) carnet lichen, 1 fougere", $text);

        self::created($client, "/api/events/$eventId/notebook", ['pages' => [$text, 'Rien de plus']]);

        $client->jsonRequest('GET', "/api/events/$eventId/notebook");
        $report = self::body($client);
        self::assertSame(2, $report['pages']);
        self::assertSame(['entries' => 3, 'orders' => 3, 'matching' => 1, 'differing' => 1, 'notRecorded' => 1, 'notNoted' => 1], $report['summary']);
        self::assertSame($matching, Json::string($report, 'matching', 0, 'order', 'id'));
        self::assertSame($differing, Json::string($report, 'differing', 0, 'order', 'id'));
        self::assertSame([['label' => 'Carnet Fougère', 'quantity' => 1]], Json::at($report, 'differing', 0, 'onlySold'));
        self::assertSame(2, Json::at($report, 'notRecorded', 0, 'number'));
        self::assertSame('fougère', Json::string($report, 'notRecorded', 0, 'lines', 0, 'written'));
        self::assertSame($notNoted, Json::string($report, 'notNoted', 0, 'id'));
    }

    public function testOnlyPhotosAreRecognisedAndPagesAreRequired(): void
    {
        $client = self::signedInClient();
        $eventId = self::created($client, '/api/events', ['name' => 'Japan Expo', 'location' => 'Villepinte', 'startDate' => '2026-07-09', 'endDate' => '2026-07-12']);

        $client->request('POST', '/api/notebook/recognitions', files: ['photo' => new UploadedFile(__FILE__, 'page.php', 'image/png', test: true)]);
        self::assertResponseStatusCodeSame(422);

        $client->jsonRequest('POST', "/api/events/$eventId/notebook", ['pages' => []]);
        self::assertResponseStatusCodeSame(422);
    }

    public function testTheNotebookTemplateIsConfiguredPerWorkspace(): void
    {
        $client = self::signedInClient();

        $client->jsonRequest('PUT', '/api/notebook-template', ['separation' => 'gap', 'abbreviations' => [['short' => 'stk', 'full' => 'sticker']]]);
        self::assertResponseStatusCodeSame(204);
        $client->jsonRequest('GET', '/api/notebook-template');
        self::assertSame(['separation' => 'gap', 'abbreviations' => [['short' => 'stk', 'full' => 'sticker']]], self::body($client));

        $client->jsonRequest('PUT', '/api/notebook-template', ['separation' => 'columns', 'abbreviations' => [['short' => '', 'full' => 'sticker']]]);
        self::assertResponseStatusCodeSame(422);

        self::signedInClientOf($client, 'Autre atelier')->jsonRequest('GET', '/api/notebook-template');
        self::assertSame(['separation' => 'numbered', 'abbreviations' => []], self::body($client));
    }

    public function testAnotherWorkspaceCannotScanTheNotebookOfAnEvent(): void
    {
        $client = self::signedInClient();
        $eventId = self::created($client, '/api/events', ['name' => 'Japan Expo', 'location' => 'Villepinte', 'startDate' => '2026-07-09', 'endDate' => '2026-07-12']);

        $other = self::signedInClientOf($client, 'Autre atelier');
        $other->jsonRequest('POST', "/api/events/$eventId/notebook", ['pages' => ['1) sticker']]);
        self::assertResponseStatusCodeSame(404);
        $other->jsonRequest('GET', "/api/events/$eventId/notebook");
        self::assertResponseStatusCodeSame(404);
    }

    private static function photo(): UploadedFile
    {
        return new UploadedFile(\dirname(__DIR__, 2).'/Fixtures/notebook/page.png', 'page.png', 'image/png', test: true);
    }

    private static function signedInClientOf(KernelBrowser $client, string $workspaceName): KernelBrowser
    {
        $client->loginUser(SecurityUser::fromUser(self::createMember($workspaceName)));

        return $client;
    }

    /**
     * @param array<string, mixed> $body
     */
    private static function created(KernelBrowser $client, string $uri, array $body): string
    {
        $client->jsonRequest('POST', $uri, $body);
        self::assertResponseStatusCodeSame(201, (string) $client->getResponse()->getContent());

        return Json::string(self::body($client), 'id');
    }

    /**
     * @return array<mixed>
     */
    private static function body(KernelBrowser $client): array
    {
        return Json::decode((string) $client->getResponse()->getContent());
    }
}
