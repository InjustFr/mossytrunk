<?php

declare(strict_types=1);

namespace App\Infrastructure\Connector\Etsy;

use App\Application\Integration\ExternalLine;
use App\Domain\Shared\Money;
use App\Infrastructure\Http\Json;

final class EtsyCatalogueMapper
{
    /**
     * @param array<string, mixed> $listing
     *
     * @return list<ExternalLine>
     */
    public function lines(array $listing): array
    {
        $listingId = Json::string($listing['listing_id'] ?? '');
        $title = trim(Json::string($listing['title'] ?? ''));
        $products = self::activeProducts(Json::object($listing['inventory'] ?? []));
        if ('' === $listingId || '' === $title) {
            return [];
        }

        return array_map(static function (array $product) use ($listingId, $title): ExternalLine {
            $sku = trim(Json::string($product['sku'] ?? ''));

            return new ExternalLine($listingId, $title, self::price($product), 1, self::variation($product), sku: '' === $sku ? null : $sku);
        }, $products);
    }

    /**
     * @param array<string, mixed> $inventory
     *
     * @return list<array<string, mixed>>
     */
    public static function activeProducts(array $inventory): array
    {
        return array_values(array_filter(Json::objects($inventory['products'] ?? []), static fn (array $product): bool => true !== ($product['is_deleted'] ?? false)));
    }

    /**
     * @param array<string, mixed> $product
     */
    public static function variation(array $product): ?string
    {
        $values = [];
        foreach (Json::objects($product['property_values'] ?? []) as $property) {
            foreach (\is_array($property['values'] ?? null) ? $property['values'] : [] as $value) {
                $value = trim(Json::string($value));
                if ('' !== $value) {
                    $values[] = $value;
                }
            }
        }

        return [] === $values ? null : implode(' / ', $values);
    }

    /**
     * @param array<string, mixed> $product
     */
    private static function price(array $product): Money
    {
        $price = Json::object(Json::objects($product['offerings'] ?? [])[0]['price'] ?? []);
        $divisor = (int) Json::number($price['divisor'] ?? 100);

        return Money::cents((int) round((float) Json::number($price['amount'] ?? 0) * 100 / max(1, $divisor)));
    }
}
