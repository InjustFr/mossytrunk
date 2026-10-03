<?php

declare(strict_types=1);

namespace App\Domain\Notebook;

final readonly class Reconciliation
{
    /**
     * @param list<Pairing>             $pairings
     * @param array<int, NotebookEntry> $notRecorded
     * @param list<RecordedOrder>       $notNoted
     */
    public function __construct(
        public array $pairings,
        public array $notRecorded,
        public array $notNoted,
    ) {
    }

    /**
     * @return list<Pairing>
     */
    public function matching(): array
    {
        return array_values(array_filter($this->pairings, static fn (Pairing $pairing): bool => $pairing->comparison->isExact()));
    }

    /**
     * @return list<Pairing>
     */
    public function differing(): array
    {
        return array_values(array_filter($this->pairings, static fn (Pairing $pairing): bool => !$pairing->comparison->isExact()));
    }
}
