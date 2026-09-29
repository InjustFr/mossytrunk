<?php

declare(strict_types=1);

namespace App\Application\Order\GetOrder;

use App\Application\Integration\Connectors;
use App\Domain\Order\OrderRepository;
use Symfony\Component\Uid\Ulid;

final readonly class GetOrderHandler
{
    public function __construct(
        private OrderRepository $orders,
        private Connectors $connectors,
    ) {
    }

    public function __invoke(string $orderId): OrderView
    {
        $order = $this->orders->get(Ulid::fromString($orderId));

        return OrderView::fromOrder($order, $this->connectors->labelOf($order->source()));
    }
}
