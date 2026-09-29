<?php

declare(strict_types=1);

namespace App\Application\Etsy;

final readonly class EtsyShop
{
    public function __construct(
        public string $id,
        public string $name,
    ) {
    }
}
