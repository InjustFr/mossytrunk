<?php

declare(strict_types=1);

namespace App\Application\Etsy;

final readonly class EtsyApp
{
    public function __construct(
        public string $keystring,
        public string $sharedSecret,
    ) {
    }
}
