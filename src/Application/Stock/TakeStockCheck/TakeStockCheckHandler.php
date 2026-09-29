<?php

declare(strict_types=1);

namespace App\Application\Stock\TakeStockCheck;

use App\Application\Transaction;
use App\Domain\Event\EventRepository;
use App\Domain\Product\ProductRepository;
use App\Domain\Stock\StockCheck;
use App\Domain\Stock\StockCheckRepository;
use App\Domain\Stock\StockCount;
use App\Domain\Stock\StockRepository;
use Psr\Clock\ClockInterface;
use Symfony\Component\Uid\Ulid;

final readonly class TakeStockCheckHandler
{
    public function __construct(
        private EventRepository $events,
        private ProductRepository $products,
        private StockRepository $stock,
        private StockCheckRepository $checks,
        private ClockInterface $clock,
        private Transaction $transaction,
    ) {
    }

    public function __invoke(TakeStockCheck $command): Ulid
    {
        $event = $this->events->get(Ulid::fromString($command->eventId));
        $counts = array_map(function (CountedItem $counted): StockCount {
            $product = $this->products->get(Ulid::fromString($counted->productId));

            return new StockCount($this->stock->for($product, $counted->variant), $counted->counted, $product->buyingPrice());
        }, $command->items);

        $check = StockCheck::take($event, $this->clock->now(), $counts);
        $this->checks->add($check);
        $this->transaction->commit();

        return $check->id();
    }
}
