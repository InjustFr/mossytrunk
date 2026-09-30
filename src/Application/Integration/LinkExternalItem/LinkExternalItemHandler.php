<?php

declare(strict_types=1);

namespace App\Application\Integration\LinkExternalItem;

use App\Application\Transaction;
use App\Domain\Integration\ExternalItemRepository;
use App\Domain\Product\ProductRepository;
use App\Domain\Shared\Exception\NotFound;
use Symfony\Component\Uid\Ulid;

final readonly class LinkExternalItemHandler
{
    public function __construct(
        private ExternalItemRepository $items,
        private ProductRepository $products,
        private Transaction $transaction,
    ) {
    }

    public function __invoke(string $service, string $itemId, string $productId, ?string $variant): void
    {
        $item = $this->items->get(Ulid::fromString($itemId));
        if ($item->service() !== $service) {
            throw new NotFound('external_item', $itemId);
        }

        $item->link($this->products->get(Ulid::fromString($productId))->sellable($variant));
        $this->transaction->commit();
    }
}
