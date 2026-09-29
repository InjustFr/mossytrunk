<?php

declare(strict_types=1);

namespace App\Application\Integration\ImportSales;

use App\Application\Integration\ExternalLine;
use App\Application\Integration\LinePrices;
use App\Domain\Integration\ExternalItem;
use App\Domain\Integration\ExternalItemRepository;
use App\Domain\Integration\ServiceConnection;
use App\Domain\Integration\UnknownItems;
use App\Domain\Product\InvalidProduct;
use App\Domain\Product\Product;
use App\Domain\Product\SellableItem;

final class ExternalItemResolver
{
    /** @var array<string, ExternalItem> */
    private array $remembered = [];

    public function __construct(
        private readonly ImportedCatalogue $catalogue,
        private readonly ExternalItemRepository $items,
        private readonly ServiceConnection $connection,
        private readonly LinePrices $linePrices,
        private readonly \DateTimeImmutable $now,
    ) {
        foreach ($this->items->ofService($connection->service()) as $item) {
            $this->remembered[$item->itemKey()] = $item;
        }
    }

    public function resolve(ExternalLine $line): ?SellableItem
    {
        $name = trim($line->name);
        if ('' === $name) {
            return $this->catalogue->freeAmount($line->unitPrice)->sellable(null)->at($line->unitPrice);
        }

        $variant = null === $line->variant || '' === trim($line->variant) ? null : trim($line->variant);
        $remembered = $this->remembered[ExternalItem::keyOf($line->externalRef, $variant)] ?? null;

        $item = null !== $remembered && $remembered->isLinked() ? $this->linked($remembered, $line) : null;
        $item ??= $this->matched($name, $variant, $line);
        if (UnknownItems::CreateProduct === $this->connection->unknownItems()) {
            $item ??= $this->created($name, $variant, $line);
        }
        if (null !== $item) {
            return $item;
        }

        $this->remember($remembered, $name, $variant, $line);

        return null;
    }

    public function itemsToLink(): int
    {
        return \count(array_filter($this->remembered, static fn (ExternalItem $item): bool => !$item->isLinked()));
    }

    private function linked(ExternalItem $remembered, ExternalLine $line): ?SellableItem
    {
        $productId = $remembered->productId();
        $product = null === $productId ? null : $this->catalogue->withId($productId);

        return null === $product ? null : $this->sold($product, $remembered->variant(), $line);
    }

    private function matched(string $name, ?string $variant, ExternalLine $line): ?SellableItem
    {
        $byReference = null === $line->sku ? null : $this->catalogue->withReference($line->sku);
        if (null !== $byReference) {
            return $this->withVariant($byReference, $variant, $line);
        }

        $product = $this->catalogue->named($name);
        if (null !== $variant) {
            return $this->matchedWithVariant($product, $name, $variant, $line);
        }
        if (null !== $product) {
            return $product->hasVariants() ? null : $this->sold($product, null, $line);
        }

        foreach (self::splitVariant($name) as [$productName, $candidate]) {
            $named = $this->catalogue->named($productName);
            $known = null === $named ? null : self::matchingVariant($named, $candidate);
            if (null !== $named && null !== $known) {
                return $this->sold($named, $known, $line);
            }
        }

        return null;
    }

    private function matchedWithVariant(?Product $product, string $name, string $variant, ExternalLine $line): ?SellableItem
    {
        $known = null === $product ? null : self::matchingVariant($product, $variant);
        if (null !== $product && null !== $known) {
            return $this->sold($product, $known, $line);
        }

        foreach ([\sprintf('%s %s', $name, $variant), \sprintf('%s - %s', $name, $variant), \sprintf('%s (%s)', $name, $variant)] as $splitName) {
            $split = $this->catalogue->named($splitName);
            if (null !== $split && !$split->hasVariants()) {
                return $this->sold($split, null, $line);
            }
        }

        if (null !== $product && !$product->hasVariants() && !$this->catalogue->wasCreated($product)) {
            return $this->sold($product, null, $line);
        }

        return null;
    }

    private function withVariant(Product $product, ?string $variant, ExternalLine $line): ?SellableItem
    {
        if (!$product->hasVariants()) {
            return $this->sold($product, null, $line);
        }
        $known = null === $variant ? null : self::matchingVariant($product, $variant);

        return null === $known ? null : $this->sold($product, $known, $line);
    }

    private function created(string $name, ?string $variant, ExternalLine $line): ?SellableItem
    {
        $product = $this->catalogue->named($name);
        if (null === $variant) {
            return null === $product ? $this->sold($this->catalogue->createFor($name, $line->category, $line->unitPrice), null, $line) : null;
        }

        $product ??= $this->catalogue->createFor($name, $line->category, $line->unitPrice);
        $product->addVariant($variant);

        return $this->sold($product, $variant, $line);
    }

    private function sold(Product $product, ?string $variant, ExternalLine $line): ?SellableItem
    {
        if ($this->catalogue->wasCreated($product) && $line->unitPrice->greaterThan($product->sellingPrice())) {
            $product->reprice($line->unitPrice);
        }

        try {
            $item = $product->sellable($variant);
        } catch (InvalidProduct) {
            return null;
        }

        if (LinePrices::Listed === $this->linePrices) {
            return $item->at($line->unitPrice);
        }

        return $line->unitPrice->greaterThan($item->sellingPrice) ? $item->at($line->unitPrice) : $item;
    }

    private function remember(?ExternalItem $remembered, string $name, ?string $variant, ExternalLine $line): void
    {
        if (null !== $remembered) {
            $remembered->seenAgain($name, $this->now);

            return;
        }

        $item = ExternalItem::seen($this->connection->workspace(), $this->connection->service(), $line->externalRef, $name, $variant, $this->now);
        $this->items->add($item);
        $this->remembered[$item->itemKey()] = $item;
    }

    private static function matchingVariant(Product $product, string $label): ?string
    {
        foreach ($product->variants() as $variant) {
            if (mb_strtolower($variant) === mb_strtolower(trim($label))) {
                return $variant;
            }
        }

        return null;
    }

    /**
     * @return list<array{string, string}>
     */
    private static function splitVariant(string $name): array
    {
        $candidates = [];
        if (preg_match('/^(.+?)\s+-\s+(.+)$/u', $name, $matches)) {
            $candidates[] = [trim($matches[1]), trim($matches[2])];
        }
        if (preg_match('/^(.+?)\s*\((.+)\)$/u', $name, $matches)) {
            $candidates[] = [trim($matches[1]), trim($matches[2])];
        }
        $words = preg_split('/\s+/u', $name) ?: [];
        for ($cut = \count($words) - 1; $cut > 0; --$cut) {
            $candidates[] = [implode(' ', \array_slice($words, 0, $cut)), implode(' ', \array_slice($words, $cut))];
        }

        return $candidates;
    }
}
