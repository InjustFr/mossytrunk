<?php

declare(strict_types=1);

namespace App\Domain\Notebook;

use App\Domain\Notebook\Exception\EmptyNotebookEntry;

final readonly class NotebookEntry
{
    /**
     * @param list<NotebookLine> $lines
     */
    public function __construct(
        public int $page,
        public array $lines,
    ) {
        if ([] === $lines) {
            throw new EmptyNotebookEntry();
        }
    }

    public function units(): int
    {
        return array_sum(array_map(static fn (NotebookLine $line): int => $line->quantity, $this->lines));
    }

    /**
     * @return array{page: int, lines: list<array{written: string, quantity: int, label: string, productId: ?string, variant: ?string, typeId: ?string}>}
     */
    public function toArray(): array
    {
        return ['page' => $this->page, 'lines' => array_map(static fn (NotebookLine $line): array => $line->toArray(), $this->lines)];
    }

    /**
     * @param array{page: int, lines: list<array{written: string, quantity: int, label: string, productId: ?string, variant: ?string, typeId: ?string}>} $entry
     */
    public static function fromArray(array $entry): self
    {
        return new self($entry['page'], array_map(NotebookLine::fromArray(...), $entry['lines']));
    }
}
