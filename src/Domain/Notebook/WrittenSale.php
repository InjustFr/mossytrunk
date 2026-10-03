<?php

declare(strict_types=1);

namespace App\Domain\Notebook;

final readonly class WrittenSale
{
    /**
     * @param list<WrittenItem> $items
     */
    public function __construct(
        public int $page,
        public array $items,
    ) {
    }
}
