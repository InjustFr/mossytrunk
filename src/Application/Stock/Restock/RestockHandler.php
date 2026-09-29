<?php

declare(strict_types=1);

namespace App\Application\Stock\Restock;

use App\Application\Transaction;
use App\Domain\Product\ProductRepository;
use App\Domain\Shared\Money;
use App\Domain\Stock\LotOrigin;
use App\Domain\Stock\StockRepository;
use Psr\Clock\ClockInterface;
use Symfony\Component\Uid\Ulid;

final readonly class RestockHandler
{
    public function __construct(
        private ProductRepository $products,
        private StockRepository $stock,
        private ClockInterface $clock,
        private Transaction $transaction,
    ) {
    }

    public function __invoke(Restock $command): void
    {
        $product = $this->products->get(Ulid::fromString($command->productId));
        $lot = $this->stock->for($product, $command->variant)->receive($command->quantity, Money::cents($command->totalPaidCents), LotOrigin::Purchase, $this->clock->now());
        $product->bought($lot->unitCost());

        $this->transaction->commit();
    }
}
