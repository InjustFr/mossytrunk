<?php

declare(strict_types=1);

namespace App\Application\Design\AdjustDeclination;

final readonly class AdjustDeclination
{
    /**
     * @param list<string> $variants
     */
    public function __construct(
        public string $designId,
        public string $declinationId,
        public string $productName,
        public int $sellingPriceCents,
        public array $variants = [],
    ) {
    }
}
