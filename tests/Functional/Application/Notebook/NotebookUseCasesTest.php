<?php

declare(strict_types=1);

namespace App\Tests\Functional\Application\Notebook;

use App\Application\Event\ScheduleEvent\ScheduleEvent;
use App\Application\Event\ScheduleEvent\ScheduleEventHandler;
use App\Application\Notebook\ConfigureNotebookTemplate\ConfigureNotebookTemplate;
use App\Application\Notebook\ConfigureNotebookTemplate\ConfigureNotebookTemplateHandler;
use App\Application\Notebook\GetNotebookReconciliation\GetNotebookReconciliationHandler;
use App\Application\Notebook\GetNotebookReconciliation\NotebookReconciliationView;
use App\Application\Notebook\GetNotebookTemplate\GetNotebookTemplateHandler;
use App\Application\Notebook\NotebookPage;
use App\Application\Notebook\RecognizeNotebookPage\RecognizeNotebookPageHandler;
use App\Application\Notebook\ScanNotebook\ScanNotebook;
use App\Application\Notebook\ScanNotebook\ScanNotebookHandler;
use App\Application\Order\PlaceOrder\PlaceOrder;
use App\Application\Order\PlaceOrder\PlaceOrderHandler;
use App\Application\Order\RefundOrder\RefundOrderHandler;
use App\Application\Order\RequestedLine;
use App\Domain\Notebook\Abbreviation;
use App\Domain\Notebook\Exception\InvalidNotebook;
use App\Domain\Notebook\RecognizedLine;
use App\Domain\Notebook\RecognizedPage;
use App\Domain\Notebook\SaleSeparation;
use App\Domain\Shared\Exception\NotFound;
use App\Infrastructure\Notebook\FakeHandwritingRecognizer;
use App\Tests\Support\ActsAsUser;
use App\Tests\Support\CreatesProducts;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Uid\Ulid;

final class NotebookUseCasesTest extends KernelTestCase
{
    use ActsAsUser;
    use CreatesProducts;

    private string $eventId;
    private string $sticker;
    private string $print;

    protected function setUp(): void
    {
        self::actAsMemberOf();
        $this->eventId = (string) self::getContainer()->get(ScheduleEventHandler::class)(
            new ScheduleEvent('Japan Expo', 'Villepinte', new \DateTimeImmutable('2026-07-09'), new \DateTimeImmutable('2026-07-12')),
        );
        $this->sticker = self::createProduct('Sticker Mousse', 400);
        $this->print = self::createProduct('Tirage Dragon', 1_500, variants: ['A4', 'A3']);
    }

    public function testThePagesAreSplitIntoSalesMatchedToTheCatalogueAndCompared(): void
    {
        $this->place('2026-07-10 10:00', new RequestedLine($this->sticker, null, 2));
        $this->place('2026-07-10 11:00', new RequestedLine($this->print, 'A3', 1));

        $this->scan("Samedi\n1) 2 sticker mousse 8€\n2) tirage dragon A4");

        $report = $this->report();
        self::assertSame(1, $report->summary['matching']);
        self::assertSame(1, $report->summary['differing']);
        self::assertSame([['label' => 'Tirage Dragon — A4', 'quantity' => 1]], $report->differing[0]['onlyNoted']);
        self::assertSame([['label' => 'Tirage Dragon — A3', 'quantity' => 1]], $report->differing[0]['onlySold']);
    }

    public function testThePagesFollowTheWorkspaceTemplate(): void
    {
        $this->place('2026-07-10 10:00', new RequestedLine($this->sticker, null, 1));
        $this->place('2026-07-10 11:00', new RequestedLine($this->print, 'A4', 2));
        self::getContainer()->get(ConfigureNotebookTemplateHandler::class)(new ConfigureNotebookTemplate(SaleSeparation::OnePerLine, [new Abbreviation('stk', 'sticker mousse'), new Abbreviation('dr', 'dragon')]));

        $this->scan("stk\n2 dr A4");

        self::assertSame(2, $this->report()->summary['matching']);
        self::assertEquals(['separation' => 'line', 'abbreviations' => [['short' => 'stk', 'full' => 'sticker mousse'], ['short' => 'dr', 'full' => 'dragon']]], (array) self::getContainer()->get(GetNotebookTemplateHandler::class)());
    }

    public function testANewWorkspaceStartsWithNumberedSalesAndNoAbbreviation(): void
    {
        self::assertEquals(['separation' => 'numbered', 'abbreviations' => []], (array) self::getContainer()->get(GetNotebookTemplateHandler::class)());
    }

    public function testAPhotoIsReadIntoTextWithGapsAsBlankLines(): void
    {
        self::getContainer()->get(FakeHandwritingRecognizer::class)->willRecognize(new RecognizedPage([
            new RecognizedLine('2 lichen', 100),
            new RecognizedLine('fougère', 150),
            new RecognizedLine('badge', 300),
        ]));

        self::assertSame("2 lichen\nfougère\n\nbadge", self::getContainer()->get(RecognizeNotebookPageHandler::class)(new NotebookPage('page.jpg', 'image/jpeg', 'photo')));
    }

    public function testScanningAgainReplacesThePreviousReading(): void
    {
        $order = $this->place('2026-07-10 10:00', new RequestedLine($this->sticker, null, 1));
        $this->scan('1) tirage dragon');
        self::assertSame(1, $this->report()->summary['notNoted']);

        $this->scan('1) sticker mousse', 'Dimanche');

        $report = $this->report();
        self::assertSame(2, $report->pages);
        self::assertSame(['entries' => 1, 'orders' => 1, 'matching' => 1, 'differing' => 0, 'notRecorded' => 0, 'notNoted' => 0], $report->summary);
        self::assertSame((string) $order, $report->matching[0]['order']['id']);
    }

    public function testRefundedOrdersAreStillComparedAndFlagged(): void
    {
        $order = $this->place('2026-07-10 10:00', new RequestedLine($this->sticker, null, 1));
        self::getContainer()->get(RefundOrderHandler::class)((string) $order);
        $this->scan('1) sticker mousse');

        self::assertTrue($this->report()->matching[0]['order']['refunded']);
    }

    public function testAnEventWithoutScanHasNoReport(): void
    {
        self::assertNull(self::getContainer()->get(GetNotebookReconciliationHandler::class)($this->eventId));
    }

    public function testNothingReadOnThePagesIsRejected(): void
    {
        $this->expectException(InvalidNotebook::class);
        $this->scan("Samedi\n12€ CB");
    }

    public function testAnotherWorkspaceDoesNotSeeTheScan(): void
    {
        $this->scan('1) sticker mousse');

        self::actAsMemberOf('Autre atelier');

        $this->expectException(NotFound::class);
        $this->report();
    }

    private function scan(string ...$pages): void
    {
        self::getContainer()->get(ScanNotebookHandler::class)(new ScanNotebook($this->eventId, array_values($pages)));
        self::getContainer()->get(EntityManagerInterface::class)->clear();
    }

    private function report(): NotebookReconciliationView
    {
        $report = self::getContainer()->get(GetNotebookReconciliationHandler::class)($this->eventId);
        self::assertNotNull($report);

        return $report;
    }

    private function place(string $placedAt, RequestedLine ...$lines): Ulid
    {
        return self::getContainer()->get(PlaceOrderHandler::class)(new PlaceOrder(new \DateTimeImmutable($placedAt, new \DateTimeZone('Europe/Paris')), array_values($lines)))->id();
    }
}
