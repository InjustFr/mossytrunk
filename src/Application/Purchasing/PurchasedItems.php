<?php

declare(strict_types=1);

namespace App\Application\Purchasing;

use App\Domain\Product\ProductRepository;
use App\Domain\Purchasing\PurchasedItem;
use App\Domain\Shared\Money;
use Symfony\Component\Uid\Ulid;

final readonly class PurchasedItems
{
    public function __construct(
        private ProductRepository $products,
    ) {
    }

    /**
     * @param list<PurchaseLine> $lines
     *
     * @return list<PurchasedItem>
     */
    public function of(array $lines): array
    {
        return array_map(fn (PurchaseLine $line): PurchasedItem => new PurchasedItem(
            $this->products->get(Ulid::fromString($line->productId))->sellable($line->variant),
            $line->quantity,
            Money::cents($line->totalPriceCents),
        ), $lines);
    }
}
