<?php

declare(strict_types=1);

namespace App\Application\Etsy;

use App\Domain\Shared\Money;

final readonly class EtsyReceipt
{
    /**
     * @param list<EtsyLine> $lines
     */
    public function __construct(
        public string $receiptId,
        public \DateTimeImmutable $createdAt,
        public array $lines,
        public Money $discount,
        public Money $shipping,
    ) {
    }
}
