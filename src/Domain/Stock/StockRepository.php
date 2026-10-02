<?php

declare(strict_types=1);

namespace App\Domain\Stock;

use App\Domain\Product\Product;
use Symfony\Component\Uid\Ulid;

interface StockRepository
{
    public function add(StockItem $item): void;

    public function remove(StockItem $item): void;

    public function for(Product $product, ?string $variant): StockItem;

    public function find(Ulid $productId, ?string $variant): ?StockItem;

    /**
     * @return list<StockItem>
     */
    public function ofProduct(Ulid $productId): array;

    /**
     * @return list<StockItem> the items holding a lot received from that supplier order
     */
    public function receivedFrom(Ulid $supplierOrderId): array;

    /**
     * @return list<StockItem>
     */
    public function all(): array;
}
