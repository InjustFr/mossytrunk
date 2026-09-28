<?php

declare(strict_types=1);

namespace App\Infrastructure\SumUp;

use App\Application\SumUp\SumUpLine;
use App\Application\SumUp\SumUpTransaction;
use App\Domain\Shared\Money;

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
        $paid = self::cents($transaction['amount'] ?? 0) - self::cents($transaction['tip_amount'] ?? 0);

        return new SumUpTransaction(
            (string) $transaction['transaction_code'],
            new \DateTimeImmutable((string) $transaction['timestamp']),
            Money::cents($paid),
            array_map(self::line(...), $transaction['products'] ?? []),
        );
    }

    /**
     * @param array<string, mixed> $product
     */
    private static function line(array $product): SumUpLine
    {
        return new SumUpLine(
            (string) $product['name'],
            Money::cents(self::cents($product['price_with_vat'] ?? $product['price'] ?? 0)),
            max(1, (int) ($product['quantity'] ?? 1)),
        );
    }

    private static function cents(int|float|string $euros): int
    {
        return (int) round((float) $euros * 100);
    }
}
