<?php

declare(strict_types=1);

namespace App\Domain\Purchasing;

use App\Domain\Product\SellableItem;
use App\Domain\Purchasing\Exception\UnknownProductPurchased;
use App\Domain\Shared\Money;
use Symfony\Component\Uid\Ulid;

final readonly class PurchasedItem
{
    public Ulid $productId;

    public function __construct(
        public SellableItem $item,
        public int $quantity,
        public Money $totalPrice,
        public ?int $received = null,
    ) {
        $this->productId = $item->productId ?? throw new UnknownProductPurchased($item->label());
    }
}
