<?php

declare(strict_types=1);

namespace App\Application\Integration;

use App\Domain\Shared\Money;

final readonly class CatalogueItem
{
    /**
     * @param list<string> $variants
     */
    public function __construct(
        public string $name,
        public string $category,
        public string $reference,
        public Money $price,
        public array $variants,
    ) {
    }
}
