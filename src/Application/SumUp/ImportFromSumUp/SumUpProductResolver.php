<?php

declare(strict_types=1);

namespace App\Application\SumUp\ImportFromSumUp;

use App\Application\Product\CreateProductType\CreateProductTypeHandler;
use App\Application\SumUp\SumUpLine;
use App\Application\WorkspaceContext;
use App\Domain\Product\Product;
use App\Domain\Product\ProductReferenceGenerator;
use App\Domain\Product\ProductRepository;
use App\Domain\Product\ProductType;
use App\Domain\Product\ProductTypeRepository;
use App\Domain\Product\SellableItem;
use App\Domain\Shared\Money;

/**
 * Maps a SumUp line name to a (product, variant) tuple, comparing with products' display names ("Print Forêt"):
 *  1. a product without variants displayed exactly like the line (case-insensitive);
 *  2. "<product> - <variant>" or "<product> (<variant>)" where the variant belongs to the product;
 *  3. otherwise a new product without variants is created (selling price from SumUp, buying price 0).
 *     When SumUp gives a category, it becomes the product type (created if needed) and a leading
 *     "<category> " is removed from the name, so "Print Forêt" in category Print becomes type Print + name Forêt.
 * Returns null when the line names a product that has variants without a known variant.
 */
final class SumUpProductResolver
{
    /** @var array<string, Product>|null products by lowercase display name */
    private ?array $byDisplayName = null;

    private int $productsCreated = 0;

    /** @var array<string, ProductType> types by lowercase name, including the ones created by this import */
    private array $types = [];

    private int $typesCreated = 0;

    public function __construct(
        private readonly ProductRepository $products,
        private readonly ProductReferenceGenerator $references,
        private readonly ProductTypeRepository $typeRepository,
        private readonly CreateProductTypeHandler $createType,
        private readonly WorkspaceContext $workspace,
    ) {
    }

    public function resolve(SumUpLine $line): ?SellableItem
    {
        $name = trim($line->name);

        $product = $this->find($name);
        if (null !== $product) {
            return $product->hasVariants() ? null : $product->sellable(null);
        }

        foreach (self::splitVariant($name) as [$productName, $variant]) {
            $product = $this->find($productName);
            if (null !== $product && $product->hasVariant($variant)) {
                return $product->sellable($variant);
            }
        }

        $type = null === $line->category || '' === trim($line->category) ? null : $this->type(trim($line->category));
        $ownName = null === $type ? $name : self::withoutPrefix($name, $type->name());

        $product = Product::create($this->workspace->current(), $this->references->generate($type, $ownName), $ownName, $line->unitPrice, Money::zero(), [], $type);
        $this->products->add($product);
        $this->index()[mb_strtolower($product->displayName())] = $product;
        ++$this->productsCreated;

        return $product->sellable(null);
    }

    public function createdCount(): int
    {
        return $this->productsCreated;
    }

    public function typesCreatedCount(): int
    {
        return $this->typesCreated;
    }

    private function find(string $displayName): ?Product
    {
        return $this->index()[mb_strtolower($displayName)] ?? null;
    }

    /**
     * @return array<string, Product>
     */
    private function &index(): array
    {
        if (null === $this->byDisplayName) {
            $this->byDisplayName = [];
            foreach ($this->products->all() as $product) {
                $this->byDisplayName[mb_strtolower($product->displayName())] ??= $product;
            }
        }

        return $this->byDisplayName;
    }

    private function type(string $name): ProductType
    {
        $key = mb_strtolower($name);
        if (!isset($this->types[$key])) {
            $existing = $this->typeRepository->findByName($name);
            if (null === $existing) {
                $existing = $this->createType->create($name);
                ++$this->typesCreated;
            }
            $this->types[$key] = $existing;
        }

        return $this->types[$key];
    }

    private static function withoutPrefix(string $name, string $typeName): string
    {
        $prefix = $typeName.' ';
        if (0 === mb_stripos($name, $prefix) && '' !== trim(mb_substr($name, mb_strlen($prefix)))) {
            return trim(mb_substr($name, mb_strlen($prefix)));
        }

        return $name;
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
