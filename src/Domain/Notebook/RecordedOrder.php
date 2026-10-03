<?php

declare(strict_types=1);

namespace App\Domain\Notebook;

use Symfony\Component\Uid\Ulid;

final readonly class RecordedOrder
{
    /**
     * @param list<RecordedLine> $lines
     */
    public function __construct(
        public Ulid $id,
        public string $reference,
        public \DateTimeImmutable $placedAt,
        public array $lines,
        public bool $refunded = false,
    ) {
    }

    public function units(): int
    {
        return array_sum(array_map(static fn (RecordedLine $line): int => $line->quantity, $this->lines));
    }
}
