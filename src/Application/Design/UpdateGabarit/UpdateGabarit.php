<?php

declare(strict_types=1);

namespace App\Application\Design\UpdateGabarit;

final readonly class UpdateGabarit
{
    /**
     * @param list<string> $variants
     * @param list<string> $adaptations
     */
    public function __construct(
        public string $gabaritId,
        public string $name,
        public ?string $typeId,
        public int $sellingPriceCents,
        public array $variants = [],
        public array $adaptations = [],
    ) {
    }
}
