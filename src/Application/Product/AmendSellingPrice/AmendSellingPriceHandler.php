<?php

declare(strict_types=1);

namespace App\Application\Product\AmendSellingPrice;

use App\Application\Transaction;
use App\Domain\Product\ProductRepository;
use App\Domain\Shared\DateRange;
use App\Domain\Shared\Money;
use Psr\Clock\ClockInterface;
use Symfony\Component\Uid\Ulid;

final readonly class AmendSellingPriceHandler
{
    public function __construct(
        private ProductRepository $products,
        private ClockInterface $clock,
        private Transaction $transaction,
    ) {
    }

    public function __invoke(string $productId, string $changeId, int $priceCents, string $sinceDay): void
    {
        $this->products->get(Ulid::fromString($productId))->amendPrice(
            Ulid::fromString($changeId),
            Money::cents($priceCents),
            new \DateTimeImmutable($sinceDay, new \DateTimeZone(DateRange::TIMEZONE)),
            $this->clock->now(),
        );
        $this->transaction->commit();
    }
}
