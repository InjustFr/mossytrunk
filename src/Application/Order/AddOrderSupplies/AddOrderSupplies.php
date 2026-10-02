<?php

declare(strict_types=1);

namespace App\Application\Order\AddOrderSupplies;

final readonly class AddOrderSupplies
{
    /**
     * @param list<string> $orderIds
     */
    public function __construct(
        public array $orderIds,
        public string $supplyId,
        public ?string $variant,
        public int $quantity,
    ) {
    }
}
