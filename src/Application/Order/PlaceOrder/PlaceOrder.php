<?php

declare(strict_types=1);

namespace App\Application\Order\PlaceOrder;

use App\Application\Order\RequestedLine;

final readonly class PlaceOrder
{
    /**
     * @param list<RequestedLine> $lines
     */
    public function __construct(
        public \DateTimeImmutable $placedAt,
        public array $lines,
    ) {
    }
}
