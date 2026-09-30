<?php

declare(strict_types=1);

namespace App\Application\Order\IdentifyOrderLine;

final readonly class IdentifyOrderLine
{
    public function __construct(
        public string $orderId,
        public string $lineId,
        public string $productId,
        public ?string $variant = null,
    ) {
    }
}
