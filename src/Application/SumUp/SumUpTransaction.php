<?php

declare(strict_types=1);

namespace App\Application\SumUp;

use App\Domain\Shared\Money;

/**
 * A successful SumUp payment, as needed to import it as an order.
 */
final readonly class SumUpTransaction
{
    /**
     * @param list<SumUpLine> $lines
     */
    public function __construct(
        public string $code,
        public \DateTimeImmutable $createdAt,
        public Money $amountPaid,
        public array $lines,
    ) {
    }
}
