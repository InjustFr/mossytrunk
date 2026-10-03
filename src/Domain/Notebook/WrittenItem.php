<?php

declare(strict_types=1);

namespace App\Domain\Notebook;

final readonly class WrittenItem
{
    public function __construct(
        public string $written,
        public int $quantity,
    ) {
    }
}
