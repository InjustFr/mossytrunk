<?php

declare(strict_types=1);

namespace App\Application\Purchasing;

use App\Domain\Purchasing\Currency;
use App\Domain\Purchasing\SupplierOrder;

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
        public ?string $supplierReference = null,
        public ?\DateTimeImmutable $receivedOn = null,
        public Currency $currency = Currency::Euro,
        public int $exchangeRateMicros = SupplierOrder::EURO_RATE,
    ) {
    }
}
