<?php

declare(strict_types=1);

namespace App\Infrastructure\Etsy;

use App\Application\Etsy\EtsyLine;
use App\Application\Etsy\EtsyReceipt;
use App\Domain\Shared\Money;
use App\Infrastructure\Http\Json;

final class EtsyPayloadMapper
{
    /**
     * @param array<string, mixed> $receipt
     */
    public function receipt(array $receipt): EtsyReceipt
    {
        return new EtsyReceipt(
            Json::string($receipt['receipt_id'] ?? ''),
            new \DateTimeImmutable('@'.Json::string($receipt['create_timestamp'] ?? $receipt['created_timestamp'] ?? '0')),
            array_map(self::line(...), Json::objects($receipt['transactions'] ?? [])),
            self::money($receipt['discount_amt'] ?? null),
            self::money($receipt['total_shipping_cost'] ?? null),
        );
    }

    /**
     * @param array<string, mixed> $transaction
     */
    private static function line(array $transaction): EtsyLine
    {
        $sku = trim(Json::string($transaction['sku'] ?? ''));
        $variations = array_values(array_filter(array_map(
            static fn (array $variation): string => trim(Json::string($variation['formatted_value'] ?? '')),
            Json::objects($transaction['variations'] ?? []),
        ), static fn (string $value): bool => '' !== $value));

        return new EtsyLine(
            Json::string($transaction['listing_id'] ?? ''),
            trim(Json::string($transaction['title'] ?? '')),
            '' === $sku ? null : $sku,
            [] === $variations ? null : implode(' / ', $variations),
            max(1, (int) Json::number($transaction['quantity'] ?? 1)),
            self::money($transaction['price'] ?? null),
        );
    }

    private static function money(mixed $amount): Money
    {
        $amount = Json::object($amount);
        $divisor = (int) Json::number($amount['divisor'] ?? 100);

        return Money::cents((int) round((float) Json::number($amount['amount'] ?? 0) * 100 / max(1, $divisor)));
    }
}
