<?php

declare(strict_types=1);

namespace App\Application\Stock\Restock;

final readonly class Restock
{
    public function __construct(
        public string $productId,
        public ?string $variant,
        public int $quantity,
        public int $totalPaidCents,
    ) {
    }
}
