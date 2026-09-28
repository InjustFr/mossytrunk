<?php

declare(strict_types=1);

namespace App\Application\SumUp;

use App\Domain\Shared\Money;

final readonly class SumUpLine
{
    public function __construct(
        public string $name,
        public Money $unitPrice,
        public int $quantity,
        public ?string $category = null,
        public ?string $variant = null,
    ) {
    }
}
