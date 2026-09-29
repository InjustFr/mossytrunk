<?php

declare(strict_types=1);

namespace App\Application\Etsy\ListEtsyListings;

use App\Domain\Etsy\EtsyListing;
use App\Domain\Etsy\EtsyListingRepository;
use App\Domain\Product\Product;
use App\Domain\Product\ProductRepository;

final readonly class ListEtsyListingsHandler
{
    public function __construct(
        private EtsyListingRepository $listings,
        private ProductRepository $products,
    ) {
    }

    /**
     * @return list<EtsyListingView>
     */
    public function __invoke(): array
    {
        $products = [];
        foreach ($this->products->all() as $product) {
            $products[(string) $product->id()] = $product;
        }

        $views = array_map(static function (EtsyListing $listing) use ($products): EtsyListingView {
            $product = null === $listing->productId() ? null : ($products[(string) $listing->productId()] ?? null);

            return new EtsyListingView(
                (string) $listing->id(),
                $listing->listingId(),
                $listing->title(),
                $listing->variation(),
                $product instanceof Product ? ['productId' => (string) $product->id(), 'name' => $product->displayName(), 'variant' => $listing->variant()] : null,
                $listing->seenAt()->format(\DateTimeInterface::ATOM),
            );
        }, $this->listings->all());
        usort($views, static fn (EtsyListingView $a, EtsyListingView $b): int => [null !== $a->linkedTo, $b->seenAt] <=> [null !== $b->linkedTo, $a->seenAt]);

        return $views;
    }
}
