<?php

declare(strict_types=1);

namespace App\Application\Order\AddOrderSupplies;

use App\Application\Stock\StockKeeper;
use App\Application\Transaction;
use App\Domain\Order\Exception\OrdersOnSeveralChannels;
use App\Domain\Order\Order;
use App\Domain\Order\OrderedItem;
use App\Domain\Order\OrderRepository;
use App\Domain\Product\ProductRepository;
use Symfony\Component\Uid\Ulid;

final readonly class AddOrderSuppliesHandler
{
    public function __construct(
        private OrderRepository $orders,
        private ProductRepository $products,
        private StockKeeper $stock,
        private Transaction $transaction,
    ) {
    }

    public function __invoke(AddOrderSupplies $command): int
    {
        $orders = array_map(fn (string $id): Order => $this->orders->get(Ulid::fromString($id)), array_values(array_unique($command->orderIds)));
        $this->assertOneChannel($orders);
        $supply = $this->products->get(Ulid::fromString($command->supplyId))->sellable($command->variant);

        foreach ($orders as $order) {
            $used = $this->stock->withdraw($order->event(), [new OrderedItem($supply, $command->quantity)])[0];
            $order->useSupply($supply, $command->quantity, $used->cost());
        }
        $this->transaction->commit();

        return \count($orders);
    }

    /**
     * @param list<Order> $orders
     */
    private function assertOneChannel(array $orders): void
    {
        $channels = array_unique(array_map(static fn (Order $order): string => (string) $order->channel()?->id(), $orders));
        if (\count($channels) > 1) {
            throw new OrdersOnSeveralChannels();
        }
    }
}
