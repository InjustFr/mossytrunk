<?php

declare(strict_types=1);

namespace App\Tests\Support;

final class EtsyListings
{
    /**
     * @param list<array{sku: string, values: list<string>, price?: int, enabled?: bool}> $products
     *
     * @return array{listing_id: int, title: string, inventory: array<string, mixed>}
     */
    public static function listing(int $listingId, string $title, array $products): array
    {
        $varies = [] !== array_merge(...array_map(static fn (array $product): array => $product['values'], $products));

        return [
            'listing_id' => $listingId,
            'title' => $title,
            'inventory' => [
                'products' => array_map(static fn (array $product): array => [
                    'sku' => $product['sku'],
                    'is_deleted' => false,
                    'property_values' => [] === $product['values'] ? [] : [['property_id' => 513, 'property_name' => 'Format', 'scale_id' => null, 'value_ids' => [crc32($product['values'][0])], 'values' => $product['values']]],
                    'offerings' => [['quantity' => 5, 'is_enabled' => $product['enabled'] ?? true, 'is_deleted' => false, 'price' => ['amount' => $product['price'] ?? 1_800, 'divisor' => 100, 'currency_code' => 'EUR'], 'readiness_state_id' => 77]],
                ], $products),
                'price_on_property' => $varies ? [513] : [],
                'quantity_on_property' => [],
                'sku_on_property' => [],
                'readiness_state_on_property' => [],
            ],
        ];
    }
}
