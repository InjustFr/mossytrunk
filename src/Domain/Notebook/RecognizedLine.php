<?php

declare(strict_types=1);

namespace App\Domain\Notebook;

final readonly class RecognizedLine
{
    public function __construct(
        public string $text,
        public float $top,
    ) {
    }
}
