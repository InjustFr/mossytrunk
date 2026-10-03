<?php

declare(strict_types=1);

namespace App\Tests\Functional\Application\Notebook;

use App\Application\Event\ScheduleEvent\ScheduleEvent;
use App\Application\Event\ScheduleEvent\ScheduleEventHandler;
use App\Application\Notebook\GetNotebookReconciliation\GetNotebookReconciliationHandler;
use App\Application\Notebook\GetNotebookReconciliation\NotebookReconciliationView;
use App\Application\Notebook\NotebookPage;
use App\Application\Notebook\ScanNotebook\ScanNotebook;
use App\Application\Notebook\ScanNotebook\ScanNotebookHandler;
use App\Application\Order\PlaceOrder\PlaceOrder;
use App\Application\Order\PlaceOrder\PlaceOrderHandler;
use App\Application\Order\RefundOrder\RefundOrderHandler;
use App\Application\Order\RequestedLine;
use App\Domain\Notebook\Exception\InvalidNotebook;
use App\Domain\Notebook\NotebookEntry;
use App\Domain\Notebook\NotebookLine;
use App\Domain\Product\ProductRepository;
use App\Domain\Shared\Exception\NotFound;
use App\Infrastructure\Notebook\FakeNotebookReader;
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

    public function testTheReaderGetsThePagesAndTheCatalogueAndTheScanIsCompared(): void
    {
        $this->place('2026-07-10 10:00', new RequestedLine($this->sticker, null, 2));
        $this->place('2026-07-10 11:00', new RequestedLine($this->print, 'A3', 1));
        $this->reader()->willRead([
            $this->entry('2 stickers', 2, $this->sticker),
            $this->entry('dragon A4', 1, $this->print, 'A4'),
        ]);

        $this->scan(new NotebookPage('page-1.jpg', 'image/jpeg', 'photo'));

        self::assertSame(['page-1.jpg'], array_map(static fn (NotebookPage $page): string => $page->name, $this->reader()->pagesRead()));
        $report = $this->report();
        self::assertSame(1, $report->summary['matching']);
        self::assertSame(1, $report->summary['differing']);
        self::assertSame([['label' => 'Tirage Dragon — A4', 'quantity' => 1]], $report->differing[0]['onlyNoted']);
        self::assertSame([['label' => 'Tirage Dragon — A3', 'quantity' => 1]], $report->differing[0]['onlySold']);
    }

    public function testScanningAgainReplacesThePreviousReading(): void
    {
        $order = $this->place('2026-07-10 10:00', new RequestedLine($this->sticker, null, 1));
        $this->reader()->willRead([$this->entry('tirage', 1, $this->print, 'A4')]);
        $this->scan(new NotebookPage('a.jpg', 'image/jpeg', 'photo'));
        self::assertSame(1, $this->report()->summary['notNoted']);

        $this->reader()->willRead([$this->entry('sticker', 1, $this->sticker)]);
        $this->scan(new NotebookPage('a.jpg', 'image/jpeg', 'photo'), new NotebookPage('b.jpg', 'image/jpeg', 'photo'));

        $report = $this->report();
        self::assertSame(2, $report->pages);
        self::assertSame(['entries' => 1, 'orders' => 1, 'matching' => 1, 'differing' => 0, 'notRecorded' => 0, 'notNoted' => 0], $report->summary);
        self::assertSame((string) $order, $report->matching[0]['order']['id']);
    }

    public function testRefundedOrdersAreStillComparedAndFlagged(): void
    {
        $order = $this->place('2026-07-10 10:00', new RequestedLine($this->sticker, null, 1));
        self::getContainer()->get(RefundOrderHandler::class)((string) $order);
        $this->reader()->willRead([$this->entry('sticker', 1, $this->sticker)]);
        $this->scan(new NotebookPage('a.jpg', 'image/jpeg', 'photo'));

        self::assertTrue($this->report()->matching[0]['order']['refunded']);
    }

    public function testAnEventWithoutScanHasNoReport(): void
    {
        self::assertNull(self::getContainer()->get(GetNotebookReconciliationHandler::class)($this->eventId));
    }

    public function testNothingReadOnThePagesIsRejected(): void
    {
        $this->reader()->willRead([]);

        $this->expectException(InvalidNotebook::class);
        $this->scan(new NotebookPage('a.jpg', 'image/jpeg', 'photo'));
    }

    public function testAnotherWorkspaceDoesNotSeeTheScan(): void
    {
        $this->reader()->willRead([$this->entry('sticker', 1, $this->sticker)]);
        $this->scan(new NotebookPage('a.jpg', 'image/jpeg', 'photo'));

        self::actAsMemberOf('Autre atelier');

        $this->expectException(NotFound::class);
        $this->report();
    }

    private function scan(NotebookPage ...$pages): void
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

    private function entry(string $written, int $quantity, string $productId, ?string $variant = null): NotebookEntry
    {
        $product = self::getContainer()->get(ProductRepository::class)->get(Ulid::fromString($productId));
        $label = null === $variant ? $product->displayName() : \sprintf('%s — %s', $product->displayName(), $variant);

        return new NotebookEntry(1, [new NotebookLine($written, $quantity, $label, $product->id(), $variant, $product->type()->id())]);
    }

    private function reader(): FakeNotebookReader
    {
        return self::getContainer()->get(FakeNotebookReader::class);
    }
}
