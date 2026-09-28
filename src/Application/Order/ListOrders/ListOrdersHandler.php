<?php

declare(strict_types=1);

namespace App\Application\Order\ListOrders;

use App\Domain\Order\OrderRepository;
use Symfony\Component\Uid\Ulid;

final readonly class ListOrdersHandler
{
    public function __construct(private OrderRepository $orders)
    {
    }

    /**
     * @return list<OrderSummaryView>
     */
    public function __invoke(?string $eventId = null): array
    {
        return array_map(
            OrderSummaryView::fromOrder(...),
            $this->orders->list(null === $eventId ? null : Ulid::fromString($eventId)),
        );
    }
}
