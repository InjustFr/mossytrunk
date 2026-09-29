<?php

declare(strict_types=1);

namespace App\Infrastructure\SumUp;

use App\Application\SumUp\SumUpLine;
use App\Application\SumUp\SumUpTransaction;
use App\Domain\Order\PaymentMethod;
use App\Domain\Shared\Money;
use App\Infrastructure\Http\Json;

/**
 * Maps SumUp API transaction payloads (amounts in euros, decimals) to application objects (cents).
 */
final class SumUpPayloadMapper
{
    /**
     * @param array<string, mixed> $transaction a transaction resource (GET /v2.1/merchants/{code}/transactions?id=…)
     */
    public function transaction(array $transaction): SumUpTransaction
    {
        $paid = self::cents(Json::number($transaction['amount'] ?? 0)) - self::cents(Json::number($transaction['tip_amount'] ?? 0));

        return new SumUpTransaction(
            Json::string($transaction['transaction_code'] ?? ''),
            new \DateTimeImmutable(Json::string($transaction['timestamp'] ?? '')),
            Money::cents($paid),
            array_map(self::line(...), Json::objects($transaction['products'] ?? [])),
            self::paymentMethod($transaction['payment_type'] ?? null),
        );
    }

    private static function paymentMethod(mixed $paymentType): ?PaymentMethod
    {
        if (!\is_string($paymentType) || '' === trim($paymentType)) {
            return null;
        }

        return 'CASH' === strtoupper(trim($paymentType)) ? PaymentMethod::Cash : PaymentMethod::Card;
    }

    /**
     * @param array<string, mixed> $product
     */
    private static function line(array $product): SumUpLine
    {
        return new SumUpLine(
            Json::string($product['name'] ?? ''),
            Money::cents(self::cents(Json::number($product['price_with_vat'] ?? $product['price'] ?? 0))),
            max(1, (int) Json::number($product['quantity'] ?? 1)),
            self::category($product),
            self::variant($product),
        );
    }

    /**
     * @param array<string, mixed> $product
     */
    private static function variant(array $product): ?string
    {
        $description = $product['description'] ?? null;

        return \is_string($description) && '' !== trim($description) ? trim($description) : null;
    }

    /**
     * Not documented on transaction products: read defensively when SumUp sends it.
     *
     * @param array<string, mixed> $product
     */
    private static function category(array $product): ?string
    {
        $category = $product['category'] ?? $product['category_name'] ?? null;
        if (\is_array($category)) {
            $category = $category['name'] ?? null;
        }

        return \is_string($category) && '' !== trim($category) ? trim($category) : null;
    }

    private static function cents(int|float|string $euros): int
    {
        return (int) round((float) $euros * 100);
    }
}
