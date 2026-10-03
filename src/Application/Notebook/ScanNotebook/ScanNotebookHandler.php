<?php

declare(strict_types=1);

namespace App\Application\Notebook\ScanNotebook;

use App\Application\Notebook\Exception\InvalidNotebookPages;
use App\Application\Notebook\NotebookCatalogue;
use App\Application\Notebook\NotebookTemplates;
use App\Application\Transaction;
use App\Domain\Event\EventRepository;
use App\Domain\Notebook\NotebookEntry;
use App\Domain\Notebook\NotebookLine;
use App\Domain\Notebook\NotebookScan;
use App\Domain\Notebook\NotebookScanRepository;
use App\Domain\Notebook\WrittenItem;
use App\Domain\Notebook\WrittenSale;
use App\Domain\Product\ProductRepository;
use Psr\Clock\ClockInterface;
use Symfony\Component\Uid\Ulid;

final readonly class ScanNotebookHandler
{
    public const int MAX_PAGES = 30;

    public function __construct(
        private EventRepository $events,
        private ProductRepository $products,
        private NotebookTemplates $templates,
        private NotebookScanRepository $scans,
        private ClockInterface $clock,
        private Transaction $transaction,
    ) {
    }

    public function __invoke(ScanNotebook $command): Ulid
    {
        if ([] === $command->pages || \count($command->pages) > self::MAX_PAGES) {
            throw new InvalidNotebookPages(self::MAX_PAGES);
        }

        $event = $this->events->get(Ulid::fromString($command->eventId));
        $template = $this->templates->current();
        $catalogue = NotebookCatalogue::of($this->products->articles());
        $entries = array_map(
            static fn (WrittenSale $sale): NotebookEntry => new NotebookEntry($sale->page, array_map(
                static fn (WrittenItem $item): NotebookLine => $catalogue->line($item, $template->expand($item->written)),
                $sale->items,
            )),
            $template->sales($command->pages),
        );
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
