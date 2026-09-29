<?php

declare(strict_types=1);

namespace App\Application\Event\GetEventReport;

use App\Domain\Product\Product;
use App\Domain\Reporting\ProductSales;

/**
 * Groups an event's product sales for the orders recap: type → product → variants.
 * Types and display names are the products' current ones (a product with no type, or deleted, goes under « Sans type »);
 * the name snapshotted on order lines is the fallback. Every level is sorted by sales, best first.
 */
final class OrderRecap
{
    public const string UNTYPED = 'Sans type';

    /**
     * @param list<ProductSales>     $sales
     * @param array<string, Product> $products
     *
     * @return list<array{type: string, products: list<array{name: string, variants: list<array{variant: string, quantity: int, sales: int, cost: int, unknownCost: bool}>, quantity: int, sales: int, cost: int, unknownCost: bool}>, quantity: int, sales: int, cost: int, unknownCost: bool}>
     */
    public static function group(array $sales, array $products): array
    {
        /** @var array<string, RecapNode> $types */
        $types = [];
        foreach ($sales as $line) {
            $productKey = $line->productId->toRfc4122();
            $product = $products[$productKey] ?? null;
            $typeName = $product?->type()?->name() ?? self::UNTYPED;

            $type = $types[$typeName] ??= new RecapNode($typeName);
            $entry = $type->child($productKey, $product?->displayName() ?? $line->productName);
            $type->add($line);
            $entry->add($line);
            if (null !== $line->variant) {
                $entry->child($line->variant, $line->variant)->add($line);
            }
        }

        return array_map(static fn (RecapNode $type): array => ['type' => $type->label, 'products' => array_map(
            static fn (RecapNode $entry): array => ['name' => $entry->label, 'variants' => array_map(
                static fn (RecapNode $variant): array => ['variant' => $variant->label] + $variant->figures(),
                $entry->children(),
            )] + $entry->figures(),
            $type->children(),
        )] + $type->figures(), RecapNode::sorted(array_values($types)));
    }
}
