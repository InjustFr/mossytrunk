<?php

declare(strict_types=1);

namespace App\Application\Etsy\LinkEtsyListing;

use App\Application\Transaction;
use App\Domain\Etsy\EtsyListingRepository;
use App\Domain\Product\ProductRepository;
use Symfony\Component\Uid\Ulid;

final readonly class LinkEtsyListingHandler
{
    public function __construct(
        private EtsyListingRepository $listings,
        private ProductRepository $products,
        private Transaction $transaction,
    ) {
    }

    public function __invoke(string $listingId, string $productId, ?string $variant): void
    {
        $this->listings->get(Ulid::fromString($listingId))->link($this->products->get(Ulid::fromString($productId))->sellable($variant));
        $this->transaction->commit();
    }
}
