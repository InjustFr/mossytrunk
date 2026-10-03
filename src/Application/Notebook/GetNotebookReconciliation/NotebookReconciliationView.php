<?php

declare(strict_types=1);

namespace App\Application\Notebook\GetNotebookReconciliation;

use App\Domain\Notebook\Gap;
use App\Domain\Notebook\NotebookEntry;
use App\Domain\Notebook\NotebookLine;
use App\Domain\Notebook\NotebookScan;
use App\Domain\Notebook\Pairing;
use App\Domain\Notebook\Reconciliation;
use App\Domain\Notebook\RecordedLine;
use App\Domain\Notebook\RecordedOrder;

/**
 * @phpstan-type EntryView array{number: int, page: int, lines: list<array{written: string, label: string, quantity: int, identified: bool}>}
 * @phpstan-type OrderView array{id: string, reference: string, placedAt: string, refunded: bool, lines: list<array{label: string, quantity: int}>}
 * @phpstan-type GapView array{label: string, quantity: int}
 */
final readonly class NotebookReconciliationView
{
    /**
     * @param array{entries: int, orders: int, matching: int, differing: int, notRecorded: int, notNoted: int}   $summary
     * @param list<EntryView>                                                                                    $notRecorded
     * @param list<OrderView>                                                                                    $notNoted
     * @param list<array{entry: EntryView, order: OrderView, onlyNoted: list<GapView>, onlySold: list<GapView>}> $differing
     * @param list<array{entry: EntryView, order: OrderView}>                                                    $matching
     */
    public function __construct(
        public string $scannedAt,
        public int $pages,
        public array $summary,
        public array $notRecorded,
        public array $notNoted,
        public array $differing,
        public array $matching,
    ) {
    }

    /**
     * @param list<RecordedOrder> $orders
     */
    public static function of(NotebookScan $scan, array $orders, Reconciliation $reconciliation): self
    {
        $differing = $reconciliation->differing();
        $matching = $reconciliation->matching();

        return new self(
            $scan->scannedAt()->format(\DateTimeInterface::ATOM),
            $scan->pages(),
            [
                'entries' => \count($scan->entries()),
                'orders' => \count($orders),
                'matching' => \count($matching),
                'differing' => \count($differing),
                'notRecorded' => \count($reconciliation->notRecorded),
                'notNoted' => \count($reconciliation->notNoted),
            ],
            array_map(self::entry(...), array_values($reconciliation->notRecorded), array_keys($reconciliation->notRecorded)),
            array_map(self::order(...), $reconciliation->notNoted),
            array_map(static fn (Pairing $pairing): array => [
                'entry' => self::entry($pairing->entry, $pairing->entryIndex),
                'order' => self::order($pairing->order),
                'onlyNoted' => array_map(self::gap(...), $pairing->comparison->onlyNoted),
                'onlySold' => array_map(self::gap(...), $pairing->comparison->onlySold),
            ], $differing),
            array_map(static fn (Pairing $pairing): array => [
                'entry' => self::entry($pairing->entry, $pairing->entryIndex),
                'order' => self::order($pairing->order),
            ], $matching),
        );
    }

    /**
     * @return EntryView
     */
    private static function entry(NotebookEntry $entry, int $index): array
    {
        return [
            'number' => $index + 1,
            'page' => $entry->page,
            'lines' => array_map(static fn (NotebookLine $line): array => [
                'written' => $line->written,
                'label' => $line->label,
                'quantity' => $line->quantity,
                'identified' => $line->namesProduct() || $line->namesOnlyType(),
            ], $entry->lines),
        ];
    }

    /**
     * @return OrderView
     */
    private static function order(RecordedOrder $order): array
    {
        return [
            'id' => (string) $order->id,
            'reference' => $order->reference,
            'placedAt' => $order->placedAt->format(\DateTimeInterface::ATOM),
            'refunded' => $order->refunded,
            'lines' => array_map(static fn (RecordedLine $line): array => ['label' => $line->label, 'quantity' => $line->quantity], $order->lines),
        ];
    }

    /**
     * @return GapView
     */
    private static function gap(Gap $gap): array
    {
        return ['label' => $gap->label, 'quantity' => $gap->quantity];
    }
}
