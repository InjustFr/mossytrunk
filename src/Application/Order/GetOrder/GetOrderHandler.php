<?php

declare(strict_types=1);

namespace App\Application\Order\GetOrder;

use App\Domain\Order\OrderRepository;
use Symfony\Component\Uid\Ulid;

final readonly class GetOrderHandler
{
    public function __construct(private OrderRepository $orders)
    {
    }

    public function __invoke(string $orderId): OrderView
    {
        return OrderView::fromOrder($this->orders->get(Ulid::fromString($orderId)));
    }
}
