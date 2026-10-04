<?php

declare(strict_types=1);

namespace App\Application\Product\RecordSellingPrice;

use App\Application\Transaction;
use App\Domain\Product\ProductRepository;
use App\Domain\Shared\BusinessTime;
use App\Domain\Shared\Money;
use Psr\Clock\ClockInterface;
use Symfony\Component\Uid\Ulid;

final readonly class RecordSellingPriceHandler
{
    public function __construct(
        private ProductRepository $products,
        private ClockInterface $clock,
        private Transaction $transaction,
    ) {
    }

    public function __invoke(string $productId, int $priceCents, string $sinceDay): void
    {
        $this->products->get(Ulid::fromString($productId))->recordPrice(
            Money::cents($priceCents),
            BusinessTime::at($sinceDay),
            $this->clock->now(),
        );
        $this->transaction->commit();
    }
}
