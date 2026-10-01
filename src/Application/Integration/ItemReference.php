<?php

declare(strict_types=1);

namespace App\Application\Integration;

final readonly class ItemReference
{
    public function __construct(
        public string $externalRef,
        public ?string $variation,
        public string $sku,
    ) {
    }
}
