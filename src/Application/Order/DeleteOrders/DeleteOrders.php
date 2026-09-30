<?php

declare(strict_types=1);

namespace App\Application\Order\DeleteOrders;

final readonly class DeleteOrders
{
    /**
     * @param list<string> $orderIds
     */
    public function __construct(
        public array $orderIds,
    ) {
    }
}
