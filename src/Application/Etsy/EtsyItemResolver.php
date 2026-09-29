<?php

declare(strict_types=1);

namespace App\Application\Etsy;

use App\Domain\Etsy\EtsyListing;
use App\Domain\Etsy\EtsyListingRepository;
use App\Domain\Identity\Workspace;
use App\Domain\Product\InvalidProduct;
use App\Domain\Product\Product;
use App\Domain\Product\ProductRepository;
use App\Domain\Product\SellableItem;

final class EtsyItemResolver
{
    /** @var array<string, Product>|null */
    private ?array $byDisplayName = null;

    public function __construct(
        private readonly ProductRepository $products,
        private readonly EtsyListingRepository $listings,
        private readonly Workspace $workspace,
        private readonly \DateTimeImmutable $now,
    ) {
    }

    public function resolve(EtsyLine $line): ?SellableItem
    {
        $listing = $this->listings->findByKey(EtsyListing::keyOf($line->listingId, $line->variation));
        $item = null !== $listing && $listing->isLinked() ? $this->linked($listing) : $this->matched($line);
        if (null !== $item) {
            return $item->at($line->unitPrice);
        }

        if (null === $listing) {
            $this->listings->add(EtsyListing::seen($this->workspace, $line->listingId, $line->title, $line->variation, $this->now));
        } else {
            $listing->seenAgain($line->title, $this->now);
        }

        return null;
    }

    private function linked(EtsyListing $listing): ?SellableItem
    {
        $productId = $listing->productId();
        $product = null === $productId ? null : ($this->products->findByIds([$productId])[0] ?? null);

        return null === $product ? null : self::sellable($product, $listing->variant());
    }

    private function matched(EtsyLine $line): ?SellableItem
    {
        $sku = trim((string) $line->sku);
        $product = '' === $sku ? null : $this->products->findByReference($sku);
        $product ??= $this->byDisplayName()[mb_strtolower(trim($line->title))] ?? null;
        if (null === $product) {
            return null;
        }
        if (!$product->hasVariants()) {
            return self::sellable($product, null);
        }

        foreach ($product->variants() as $variant) {
            if (mb_strtolower($variant) === mb_strtolower(trim((string) $line->variation))) {
                return self::sellable($product, $variant);
            }
        }

        return null;
    }

    /**
     * @return array<string, Product>
     */
    private function byDisplayName(): array
    {
        if (null === $this->byDisplayName) {
            $this->byDisplayName = [];
            foreach ($this->products->all() as $product) {
                $this->byDisplayName[mb_strtolower($product->displayName())] = $product;
            }
        }

        return $this->byDisplayName;
    }

    private static function sellable(Product $product, ?string $variant): ?SellableItem
    {
        try {
            return $product->sellable($variant);
        } catch (InvalidProduct) {
            return null;
        }
    }
}
