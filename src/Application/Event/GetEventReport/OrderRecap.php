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
     * @param array<string, Product> $products current products by id (RFC 4122)
     *
     * @return list<array<string, mixed>>
     */
    public static function group(array $sales, array $products): array
    {
        $groups = [];
        foreach ($sales as $line) {
            $product = $products[$line->productId->toRfc4122()] ?? null;
            $type = $product?->type()?->name() ?? self::UNTYPED;
            $productKey = $line->productId->toRfc4122();

            $groups[$type] ??= ['type' => $type, 'products' => []] + self::zero();
            $groups[$type]['products'][$productKey] ??= ['name' => $product?->displayName() ?? $line->productName, 'variants' => []] + self::zero();

            self::accumulate($groups[$type], $line);
            self::accumulate($groups[$type]['products'][$productKey], $line);
            if (null !== $line->variant) {
                $groups[$type]['products'][$productKey]['variants'][] = ['variant' => $line->variant] + self::zero();
                self::accumulate($groups[$type]['products'][$productKey]['variants'][array_key_last($groups[$type]['products'][$productKey]['variants'])], $line);
            }
        }

        foreach ($groups as &$group) {
            foreach ($group['products'] as &$entry) {
                $entry['variants'] = self::sorted($entry['variants'], 'variant');
            }
            unset($entry);
            $group['products'] = self::sorted(array_values($group['products']), 'name');
        }
        unset($group);

        return self::sorted(array_values($groups), 'type');
    }

    /**
     * @return array{quantity: int, sales: int, cost: int, unknownCost: bool}
     */
    private static function zero(): array
    {
        return ['quantity' => 0, 'sales' => 0, 'cost' => 0, 'unknownCost' => false];
    }

    /**
     * @param array<string, mixed> $node
     */
    private static function accumulate(array &$node, ProductSales $line): void
    {
        $node['quantity'] += $line->quantity;
        $node['sales'] += $line->sales->amount();
        $node['cost'] += $line->cost->amount();
        $node['unknownCost'] = $node['unknownCost'] || $line->unknownCost;
    }

    /**
     * @param list<array<string, mixed>> $nodes
     *
     * @return list<array<string, mixed>>
     */
    private static function sorted(array $nodes, string $label): array
    {
        usort($nodes, static fn (array $a, array $b): int => [$b['sales'], $a[$label]] <=> [$a['sales'], $b[$label]]);

        return $nodes;
    }
}
