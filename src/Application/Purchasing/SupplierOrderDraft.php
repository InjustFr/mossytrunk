<?php

declare(strict_types=1);

namespace App\Application\Purchasing;

final readonly class SupplierOrderDraft
{
    /**
     * @param list<PurchaseLine> $lines
     */
    public function __construct(
        public string $supplierId,
        public \DateTimeImmutable $orderedOn,
        public array $lines,
        public int $discountCents = 0,
        public int $deliveryFeesCents = 0,
    ) {
    }
}
