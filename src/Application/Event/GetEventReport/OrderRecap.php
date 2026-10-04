<?php

declare(strict_types=1);

namespace App\Application\Event\GetEventReport;

use App\Domain\Product\Product;
use App\Domain\Reporting\ProductSales;

final class OrderRecap
{
    /**
     * @param list<ProductSales>     $sales
     * @param array<string, Product> $products
     *
     * @return list<array{type: string|null, products: list<array{name: string, variants: list<array{variant: string, quantity: int, sales: int, cost: int, unknownCost: bool}>, quantity: int, sales: int, cost: int, unknownCost: bool}>, quantity: int, sales: int, cost: int, unknownCost: bool}>
     */
    public static function group(array $sales, array $products): array
    {
        /** @var array<string, RecapNode> $types */
        $types = [];
        foreach ($sales as $line) {
            $productKey = (string) $line->productId;
            $product = $products[$productKey] ?? null;
            $typeName = $product?->type()?->name() ?? '';

            $type = $types[$typeName] ??= new RecapNode($typeName);
            $entry = $type->child($productKey, $product?->displayName() ?? $line->productName);
            $type->add($line);
            $entry->add($line);
            if (null !== $line->variant) {
                $entry->child($line->variant, $line->variant)->add($line);
            }
        }

        return array_map(static fn (RecapNode $type): array => ['type' => '' === $type->label ? null : $type->label, 'products' => array_map(
            static fn (RecapNode $entry): array => ['name' => $entry->label, 'variants' => array_map(
                static fn (RecapNode $variant): array => ['variant' => $variant->label] + $variant->figures(),
                $entry->children(),
            )] + $entry->figures(),
            $type->children(),
        )] + $type->figures(), RecapNode::sorted(array_values($types)));
    }
}
