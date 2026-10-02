<?php

declare(strict_types=1);

namespace App\Application\Order\FillMissingCosts;

use App\Application\Order\MissingCosts;
use App\Application\Transaction;
use App\Domain\Stock\StockRepository;

final readonly class FillMissingCostsHandler
{
    public function __construct(
        private StockRepository $stock,
        private MissingCosts $missingCosts,
        private Transaction $transaction,
    ) {
    }

    public function __invoke(): int
    {
        $filled = 0;
        foreach ($this->stock->all() as $item) {
            $filled += $this->missingCosts->fill($item);
        }
        $this->transaction->commit();

        return $filled;
    }
}
