<?php

declare(strict_types=1);

namespace App\Application\Order;

final readonly class RequestedLine
{
    public function __construct(
        public string $productId,
        public ?string $variant,
        public int $quantity,
    ) {
    }
}
