<?php

declare(strict_types=1);

namespace App\Application\Order\ListOrders;

use Symfony\Component\Uid\Ulid;

final readonly class ListOrdersHandler
{
    public function __construct(private OrderSummaries $orders)
    {
    }

    /**
     * @return list<OrderSummaryView>
     */
    public function __invoke(?string $eventId = null): array
    {
        return $this->orders->list(null === $eventId ? null : Ulid::fromString($eventId));
    }
}
