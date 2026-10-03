<?php

declare(strict_types=1);

namespace App\Application\Order\ListOrders;

use Symfony\Component\Uid\Ulid;

interface OrderSummaries
{
    /**
     * @return list<OrderSummaryView> most recent first, optionally restricted to one event
     */
    public function list(?Ulid $eventId = null): array;
}
