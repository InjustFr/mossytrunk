<?php

declare(strict_types=1);

namespace App\Application\Purchasing\ReceiveSupplierOrder;

use App\Application\Transaction;
use App\Domain\Product\ProductRepository;
use App\Domain\Purchasing\SupplierOrderRepository;
use App\Domain\Stock\LotOrigin;
use App\Domain\Stock\StockRepository;
use Psr\Clock\ClockInterface;
use Symfony\Component\Uid\Ulid;

final readonly class ReceiveSupplierOrderHandler
{
    public function __construct(
        private SupplierOrderRepository $orders,
        private ProductRepository $products,
        private StockRepository $stock,
        private ClockInterface $clock,
        private Transaction $transaction,
    ) {
    }

    /**
     * @param array<string, int> $receivedQuantities
     */
    public function __invoke(string $orderId, array $receivedQuantities): void
    {
        $order = $this->orders->get(Ulid::fromString($orderId));
        $receivedAt = $this->clock->now();

        foreach ($order->receive($receivedQuantities, $receivedAt) as $line) {
            $product = $this->products->findByIds([$line->productId()])[0] ?? null;
            if (null === $product) {
                continue;
            }
            $lot = $this->stock->for($product, $line->variant())->receive((int) $line->receivedQuantity(), $line->landedCost(), LotOrigin::SupplierOrder, $receivedAt, $order->id());
            $product->bought($lot->unitCost());
        }

        $this->transaction->commit();
    }
}
