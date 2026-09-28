<?php

declare(strict_types=1);

namespace App\Application\SumUp\ImportFromSumUp;

use App\Application\SumUp\SumUpLine;
use App\Domain\Product\Product;
use App\Domain\Product\ProductReferenceGenerator;
use App\Domain\Product\ProductRepository;
use App\Domain\Product\SellableItem;
use App\Domain\Shared\Money;

/**
 * Maps a SumUp line name to a (product, variant) tuple:
 *  1. a product without variants named exactly like the line;
 *  2. "<product> - <variant>" or "<product> (<variant>)" where the variant belongs to the product;
 *  3. otherwise a new product without variants is created (selling price from SumUp, buying price 0).
 * Returns null when the line names a product that has variants without a known variant.
 */
final class SumUpProductResolver
{
    /** @var array<string, Product> products created during this import, by name */
    private array $created = [];

    public function __construct(
        private readonly ProductRepository $products,
        private readonly ProductReferenceGenerator $references,
    ) {
    }

    public function resolve(SumUpLine $line): ?SellableItem
    {
        $name = trim($line->name);

        $product = $this->created[$name] ?? $this->products->findByName($name);
        if (null !== $product) {
            return $product->hasVariants() ? null : $product->sellable(null);
        }

        foreach (self::splitVariant($name) as [$productName, $variant]) {
            $product = $this->created[$productName] ?? $this->products->findByName($productName);
            if (null !== $product && $product->hasVariant($variant)) {
                return $product->sellable($variant);
            }
        }

        $product = Product::create($this->references->generate(null, $name), $name, $line->unitPrice, Money::zero());
        $this->products->add($product);
        $this->created[$name] = $product;

        return $product->sellable(null);
    }

    public function createdCount(): int
    {
        return \count($this->created);
    }

    /**
     * @return list<array{string, string}> candidate (product name, variant) pairs
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

        return $candidates;
    }
}
