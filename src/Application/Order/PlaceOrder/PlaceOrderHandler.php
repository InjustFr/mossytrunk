<?php

declare(strict_types=1);

namespace App\Application\Order\PlaceOrder;

use App\Application\Order\OrderPricing;
use App\Application\Stock\StockKeeper;
use App\Application\Transaction;
use App\Domain\Event\EventRepository;
use App\Domain\Order\InvalidOrder;
use App\Domain\Order\Order;
use App\Domain\Order\OrderRepository;

/**
 * Records a manual order: the event is deduced from the order date, discounts are automatic.
 */
final readonly class PlaceOrderHandler
{
    public function __construct(
        private EventRepository $events,
        private OrderRepository $orders,
        private OrderPricing $pricing,
        private StockKeeper $stock,
        private Transaction $transaction,
    ) {
    }

    public function __invoke(PlaceOrder $command): Order
    {
        $event = $this->events->findCovering($command->placedAt) ?? throw InvalidOrder::noEventAt($command->placedAt);

        $items = $this->stock->withdraw($event, $this->pricing->items($command->lines));
        $order = Order::place($event, $command->placedAt, $items, $this->pricing->discounts($items, $command->placedAt));

        $this->orders->add($order);
        $this->transaction->commit();

        return $order;
    }
}
