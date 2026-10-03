<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Notebook;

use App\Domain\Event\Event;
use App\Domain\Notebook\Exception\InvalidNotebook;
use App\Domain\Notebook\NotebookEntry;
use App\Domain\Notebook\NotebookLine;
use App\Domain\Notebook\NotebookScan;
use App\Domain\Shared\DateRange;
use App\Tests\Support\TestWorkspace;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Ulid;

final class NotebookScanTest extends TestCase
{
    private Event $event;

    protected function setUp(): void
    {
        $this->event = Event::schedule(TestWorkspace::get(), 'Japan Expo', 'Villepinte', DateRange::fromDates(new \DateTimeImmutable('2026-07-09'), new \DateTimeImmutable('2026-07-12')));
    }

    public function testAScanKeepsTheEntriesInNotebookOrder(): void
    {
        $product = new Ulid();
        $type = new Ulid();
        $entries = [
            new NotebookEntry(1, [new NotebookLine('2 dragon A4', 2, 'Dragon — A4', $product, 'A4', $type)]),
            new NotebookEntry(2, [new NotebookLine('sticker', 1, 'Sticker', typeId: $type), new NotebookLine('??', 1, '??')]),
        ];

        $scan = NotebookScan::read($this->event, new \DateTimeImmutable('2026-07-13 09:00'), 2, $entries);

        self::assertEquals($entries, $scan->entries());
        self::assertSame(2, $scan->pages());
    }

    public function testRereadingReplacesTheEntries(): void
    {
        $scan = NotebookScan::read($this->event, new \DateTimeImmutable('2026-07-13 09:00'), 1, [new NotebookEntry(1, [new NotebookLine('a', 1, 'a')])]);
        $scan->reread(new \DateTimeImmutable('2026-07-14 09:00'), 3, [new NotebookEntry(3, [new NotebookLine('b', 2, 'b')])]);

        self::assertSame('b', $scan->entries()[0]->lines[0]->written);
        self::assertSame(3, $scan->pages());
        self::assertEquals(new \DateTimeImmutable('2026-07-14 09:00'), $scan->scannedAt());
    }

    public function testAScanReadsAtLeastOneSale(): void
    {
        $this->expectException(InvalidNotebook::class);

        NotebookScan::read($this->event, new \DateTimeImmutable(), 1, []);
    }

    public function testASaleHasAtLeastOneItem(): void
    {
        $this->expectException(InvalidNotebook::class);

        new NotebookEntry(1, []);
    }

    public function testANotedQuantityIsPositive(): void
    {
        $this->expectException(InvalidNotebook::class);

        new NotebookLine('0 sticker', 0, 'Sticker');
    }
}
