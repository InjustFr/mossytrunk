<?php

declare(strict_types=1);

namespace App\Domain\Notebook;

use Symfony\Component\Uid\Ulid;

final readonly class RecordedLine
{
    public function __construct(
        public string $label,
        public int $quantity,
        public ?Ulid $productId = null,
        public ?string $variant = null,
        public ?Ulid $typeId = null,
    ) {
    }
}
