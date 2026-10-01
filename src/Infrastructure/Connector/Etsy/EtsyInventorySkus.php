<?php

declare(strict_types=1);

namespace App\Infrastructure\Connector\Etsy;

use App\Infrastructure\Http\Json;

final class EtsyInventorySkus
{
    /**
     * @param array<string, mixed>  $inventory
     * @param array<string, string> $skuByVariation
     *
     * @return array<string, mixed>|null
     */
    public function withSkus(array $inventory, array $skuByVariation): ?array
    {
        $changed = false;
        $products = [];
        foreach (EtsyCatalogueMapper::activeProducts($inventory) as $product) {
            $current = trim(Json::string($product['sku'] ?? ''));
            $sku = $skuByVariation[EtsyCatalogueMapper::variation($product) ?? ''] ?? $current;
            $changed = $changed || $sku !== $current;
            $products[] = [
                'sku' => $sku,
                'property_values' => array_map(self::propertyValue(...), Json::objects($product['property_values'] ?? [])),
                'offerings' => array_map(self::offering(...), array_values(array_filter(Json::objects($product['offerings'] ?? []), static fn (array $offering): bool => true !== ($offering['is_deleted'] ?? false)))),
            ];
        }
        if (!$changed) {
            return null;
        }

        $skus = array_unique(array_map(static fn (array $product): string => $product['sku'], $products));

        return [
            'products' => $products,
            'price_on_property' => self::propertyIds($inventory['price_on_property'] ?? []),
            'quantity_on_property' => self::propertyIds($inventory['quantity_on_property'] ?? []),
            'sku_on_property' => \count($skus) > 1 ? self::variedProperties($products) : [],
            'readiness_state_on_property' => self::propertyIds($inventory['readiness_state_on_property'] ?? []),
        ];
    }

    /**
     * @param array<string, mixed> $value
     *
     * @return array<string, mixed>
     */
    private static function propertyValue(array $value): array
    {
        return array_filter([
            'property_id' => $value['property_id'] ?? null,
            'value_ids' => $value['value_ids'] ?? [],
            'scale_id' => $value['scale_id'] ?? null,
            'property_name' => $value['property_name'] ?? null,
            'values' => $value['values'] ?? [],
        ], static fn (mixed $field): bool => null !== $field);
    }

    /**
     * @param array<string, mixed> $offering
     *
     * @return array<string, mixed>
     */
    private static function offering(array $offering): array
    {
        $price = Json::object($offering['price'] ?? []);

        return array_filter([
            'price' => round((float) Json::number($price['amount'] ?? 0) / max(1, (int) Json::number($price['divisor'] ?? 100)), 2),
            'quantity' => $offering['quantity'] ?? 0,
            'is_enabled' => $offering['is_enabled'] ?? true,
            'readiness_state_id' => $offering['readiness_state_id'] ?? null,
        ], static fn (mixed $field): bool => null !== $field);
    }

    /**
     * @param list<array{sku: string, property_values: list<array<string, mixed>>, offerings: list<array<string, mixed>>}> $products
     *
     * @return list<int>
     */
    private static function variedProperties(array $products): array
    {
        $ids = [];
        foreach ($products as $product) {
            foreach ($product['property_values'] as $value) {
                $ids[] = (int) Json::number($value['property_id'] ?? 0);
            }
        }

        return array_values(array_unique(array_filter($ids)));
    }

    /**
     * @return list<int>
     */
    private static function propertyIds(mixed $ids): array
    {
        return \is_array($ids) ? array_values(array_map(static fn (mixed $id): int => (int) Json::number($id), $ids)) : [];
    }
}
