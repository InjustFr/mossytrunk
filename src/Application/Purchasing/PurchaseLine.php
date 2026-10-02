<?php

declare(strict_types=1);

namespace App\Application\Purchasing;

final readonly class PurchaseLine
{
    public function __construct(
        public string $productId,
        public ?string $variant,
        public int $quantity,
        public int $totalPriceCents,
        public ?int $received = null,
    ) {
    }
}
