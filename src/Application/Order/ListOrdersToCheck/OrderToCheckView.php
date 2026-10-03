<?php

declare(strict_types=1);

namespace App\Application\Order\ListOrdersToCheck;

final readonly class OrderToCheckView
{
    /**
     * @param list<array{label: string, quantity: int, unidentified: bool}> $lines
     */
    public function __construct(
        public string $id,
        public string $reference,
        public string $placedAt,
        public string $source,
        public string $sourceLabel,
        public ?string $paymentMethod,
        public int $total,
        public bool $refunded,
        public bool $checked,
        public array $lines,
    ) {
    }
}
