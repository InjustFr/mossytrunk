<?php

declare(strict_types=1);

namespace App\Application\Order\ListOrdersToCheck;

use Symfony\Component\Uid\Ulid;

interface OrdersToCheck
{
    /**
     * @return list<OrderToCheckView> oldest first
     */
    public function ofEvent(Ulid $eventId): array;
}
