<?php

declare(strict_types=1);

namespace App\Application\Product\BatchUpdateProducts;

use App\Domain\Product\Product;
use App\Domain\Shared\DateRange;
use App\Domain\Shared\Money;

final readonly class SellingRepricing
{
    private function __construct(
        private ?\DateTimeImmutable $since,
        private \DateTimeImmutable $now,
    ) {
    }

    public static function since(?string $day, \DateTimeImmutable $now): self
    {
        $timezone = new \DateTimeZone(DateRange::TIMEZONE);
        $today = null === $day || $now->setTimezone($timezone)->format('Y-m-d') === $day;

        return new self($today ? null : new \DateTimeImmutable($day, $timezone), $now);
    }

    public function reprice(Product $product, Money $price): void
    {
        if (null === $this->since) {
            $product->reprice($price);
        } else {
            $product->recordPrice($price, $this->since, $this->now);
        }
    }
}
