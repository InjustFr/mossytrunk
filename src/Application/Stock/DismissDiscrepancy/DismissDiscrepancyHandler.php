<?php

declare(strict_types=1);

namespace App\Application\Stock\DismissDiscrepancy;

use App\Application\Transaction;
use App\Domain\Stock\StockCheckRepository;
use Symfony\Component\Uid\Ulid;

final readonly class DismissDiscrepancyHandler
{
    public function __construct(
        private StockCheckRepository $checks,
        private Transaction $transaction,
    ) {
    }

    public function __invoke(string $checkId, string $lineId): void
    {
        $this->checks->get(Ulid::fromString($checkId))->dismiss(Ulid::fromString($lineId));
        $this->transaction->commit();
    }
}
