<?php

declare(strict_types=1);

namespace App\Domain\Event\Exception;

final class OrdersOutsidePeriod extends InvalidEvent
{
    public function __construct(int $count)
    {
        parent::__construct('event.orders_outside_period', ['count' => $count]);
    }
}
