<?php

declare(strict_types=1);

namespace App\Application\Order\ListMergeCandidates;

use App\Application\Integration\Connectors;
use App\Application\Order\ListOrders\OrderSummaryView;
use App\Domain\Order\Order;
use App\Domain\Order\OrderRepository;
use Symfony\Component\Uid\Ulid;

final readonly class ListMergeCandidatesHandler
{
    public function __construct(
        private OrderRepository $orders,
        private Connectors $connectors,
    ) {
    }

    /**
     * @return list<OrderSummaryView>
     */
    public function __invoke(string $orderId): array
    {
        $order = $this->orders->get(Ulid::fromString($orderId));

        return array_values(array_map(
            fn (Order $candidate): OrderSummaryView => OrderSummaryView::fromOrder($candidate, $this->connectors->labelOf($candidate->source())),
            array_filter($this->orders->mergeCandidatesOf($order), $order->canAbsorb(...)),
        ));
    }
}
