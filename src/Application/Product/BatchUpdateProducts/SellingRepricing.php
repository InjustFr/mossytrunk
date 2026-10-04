<?php

declare(strict_types=1);

namespace App\Application\Product\BatchUpdateProducts;

use App\Domain\Product\Product;
use App\Domain\Shared\BusinessTime;
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
        $today = null === $day || BusinessTime::day($now) === $day;

        return new self($today ? null : BusinessTime::at($day), $now);
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
