<?php

declare(strict_types=1);

namespace App\Application\Order\ListOrders;

use App\Application\Integration\Connectors;
use App\Domain\Order\Order;
use App\Domain\Order\OrderRepository;
use Symfony\Component\Uid\Ulid;

final readonly class ListOrdersHandler
{
    public function __construct(
        private OrderRepository $orders,
        private Connectors $connectors,
    ) {
    }

    /**
     * @return list<OrderSummaryView>
     */
    public function __invoke(?string $eventId = null): array
    {
        return array_map(
            fn (Order $order): OrderSummaryView => OrderSummaryView::fromOrder($order, $this->connectors->labelOf($order->source())),
            $this->orders->list(null === $eventId ? null : Ulid::fromString($eventId)),
        );
    }
}
