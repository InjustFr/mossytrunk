<?php

declare(strict_types=1);

namespace App\Domain\Notebook;

final readonly class Gap
{
    public function __construct(
        public string $label,
        public int $quantity,
    ) {
    }
}
