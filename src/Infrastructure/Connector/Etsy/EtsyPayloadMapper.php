<?php

declare(strict_types=1);

namespace App\Infrastructure\Connector\Etsy;

use App\Application\Integration\ExternalLine;
use App\Application\Integration\ExternalSale;
use App\Domain\Order\PaymentMethod;
use App\Domain\Shared\Money;
use App\Infrastructure\Http\Json;

final class EtsyPayloadMapper
{
    /**
     * @param array<string, mixed> $receipt
     */
    public function sale(array $receipt): ExternalSale
    {
        $id = Json::string($receipt['receipt_id'] ?? '');
        $lines = array_map(self::line(...), Json::objects($receipt['transactions'] ?? []));
        $listed = Money::sum(array_map(static fn (ExternalLine $line): Money => $line->unitPrice->multiply($line->quantity), $lines));

        return new ExternalSale(
            $id,
            'ETSY-'.$id,
            new \DateTimeImmutable('@'.Json::string($receipt['create_timestamp'] ?? $receipt['created_timestamp'] ?? '0')),
            $lines,
            $listed->subtract(self::money($receipt['discount_amt'] ?? null)),
            self::money($receipt['total_shipping_cost'] ?? null),
            PaymentMethod::Card,
        );
    }

    /**
     * @param array<string, mixed> $transaction
     */
    private static function line(array $transaction): ExternalLine
    {
        $sku = trim(Json::string($transaction['sku'] ?? ''));
        $variations = array_values(array_filter(array_map(
            static fn (array $variation): string => trim(Json::string($variation['formatted_value'] ?? '')),
            Json::objects($transaction['variations'] ?? []),
        ), static fn (string $value): bool => '' !== $value));

        return new ExternalLine(
            Json::string($transaction['listing_id'] ?? ''),
            trim(Json::string($transaction['title'] ?? '')),
            self::money($transaction['price'] ?? null),
            max(1, (int) Json::number($transaction['quantity'] ?? 1)),
            [] === $variations ? null : implode(' / ', $variations),
            sku: '' === $sku ? null : $sku,
        );
    }

    private static function money(mixed $amount): Money
    {
        $amount = Json::object($amount);
        $divisor = (int) Json::number($amount['divisor'] ?? 100);

        return Money::cents((int) round((float) Json::number($amount['amount'] ?? 0) * 100 / max(1, $divisor)));
    }
}
