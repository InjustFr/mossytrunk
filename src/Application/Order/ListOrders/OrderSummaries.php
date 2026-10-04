<?php

declare(strict_types=1);

namespace App\Application\Order\ListOrders;

use Symfony\Component\Uid\Ulid;

interface OrderSummaries
{
    /**
     * @return list<OrderSummaryView>
     */
    public function list(?Ulid $eventId = null): array;
}
