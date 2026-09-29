<?php

declare(strict_types=1);

namespace App\Application\Etsy;

use App\Domain\Shared\Money;

final readonly class EtsyLine
{
    public function __construct(
        public string $listingId,
        public string $title,
        public ?string $sku,
        public ?string $variation,
        public int $quantity,
        public Money $unitPrice,
    ) {
    }
}
