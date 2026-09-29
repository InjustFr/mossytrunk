<?php

declare(strict_types=1);

namespace App\Application\Integration;

use App\Domain\Shared\Money;

final readonly class ExternalLine
{
    public function __construct(
        public string $externalRef,
        public string $name,
        public Money $unitPrice,
        public int $quantity,
        public ?string $variant = null,
        public ?string $category = null,
        public ?string $sku = null,
    ) {
    }
}
