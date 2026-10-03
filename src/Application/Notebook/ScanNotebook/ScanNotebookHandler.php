<?php

declare(strict_types=1);

namespace App\Application\Notebook\ScanNotebook;

use App\Application\Notebook\Exception\InvalidNotebookPages;
use App\Application\Notebook\NotebookCatalogue;
use App\Application\Notebook\NotebookReader;
use App\Application\Transaction;
use App\Domain\Event\EventRepository;
use App\Domain\Notebook\NotebookScan;
use App\Domain\Notebook\NotebookScanRepository;
use App\Domain\Product\ProductRepository;
use Psr\Clock\ClockInterface;
use Symfony\Component\Uid\Ulid;

final readonly class ScanNotebookHandler
{
    public const int MAX_PAGES = 30;
    public const int MAX_PAGE_MEGABYTES = 5;

    public function __construct(
        private EventRepository $events,
        private ProductRepository $products,
        private NotebookReader $reader,
        private NotebookScanRepository $scans,
        private ClockInterface $clock,
        private Transaction $transaction,
    ) {
    }

    public function __invoke(ScanNotebook $command): Ulid
    {
        if ([] === $command->pages || \count($command->pages) > self::MAX_PAGES) {
            throw new InvalidNotebookPages(self::MAX_PAGES, self::MAX_PAGE_MEGABYTES);
        }

        $event = $this->events->get(Ulid::fromString($command->eventId));
        $entries = $this->reader->read($command->pages, NotebookCatalogue::of($this->products->articles()));
        $now = $this->clock->now();

        $scan = $this->scans->ofEvent($event->id());
        if (null === $scan) {
            $scan = NotebookScan::read($event, $now, \count($command->pages), $entries);
            $this->scans->add($scan);
        } else {
            $scan->reread($now, \count($command->pages), $entries);
        }
        $this->transaction->commit();

        return $scan->id();
    }
}
