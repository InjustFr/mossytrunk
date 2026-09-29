<?php

declare(strict_types=1);

namespace App\Application\Product\ReviseSellingPrices;

use App\Application\Transaction;
use App\Domain\Product\Product;
use App\Domain\Product\ProductRepository;
use App\Domain\Shared\DateRange;
use App\Domain\Shared\Money;
use Psr\Clock\ClockInterface;
use Symfony\Component\Uid\Ulid;

final readonly class ReviseSellingPricesHandler
{
    public function __construct(
        private ProductRepository $products,
        private ClockInterface $clock,
        private Transaction $transaction,
    ) {
    }

    public function record(string $productId, int $priceCents, string $sinceDay): void
    {
        $this->product($productId)->recordPrice(Money::cents($priceCents), self::day($sinceDay), $this->clock->now());
        $this->transaction->commit();
    }

    public function amend(string $productId, string $changeId, int $priceCents, string $sinceDay): void
    {
        $this->product($productId)->amendPrice(Ulid::fromString($changeId), Money::cents($priceCents), self::day($sinceDay), $this->clock->now());
        $this->transaction->commit();
    }

    public function forget(string $productId, string $changeId): void
    {
        $this->product($productId)->forgetPrice(Ulid::fromString($changeId));
        $this->transaction->commit();
    }

    private function product(string $productId): Product
    {
        return $this->products->get(Ulid::fromString($productId));
    }

    private static function day(string $day): \DateTimeImmutable
    {
        return new \DateTimeImmutable($day, new \DateTimeZone(DateRange::TIMEZONE));
    }
}
