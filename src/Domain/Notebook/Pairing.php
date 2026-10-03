<?php

declare(strict_types=1);

namespace App\Domain\Notebook;

final readonly class Pairing
{
    public function __construct(
        public int $entryIndex,
        public NotebookEntry $entry,
        public RecordedOrder $order,
        public LineComparison $comparison,
    ) {
    }
}
