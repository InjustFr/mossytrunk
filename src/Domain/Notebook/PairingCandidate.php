<?php

declare(strict_types=1);

namespace App\Domain\Notebook;

final readonly class PairingCandidate
{
    public function __construct(
        public int $entryIndex,
        public int $orderIndex,
        public LineComparison $comparison,
        public float $distance,
    ) {
    }
}
