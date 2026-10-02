<?php

declare(strict_types=1);

namespace App\Application\Reporting\ProductReport;

final readonly class ProductReportView
{
    /**
     * @param array{id: string, name: string, typeName: string, sellingPrice: int, onHand: int}                                             $product
     * @param array{from: string, to: string, days: int}                                                                                    $period
     * @param list<array{month: string, units: int, gross: int, revenue: int, received: int, sold: int, lost: int, used: int, onHand: int}> $months
     * @param list<array{id: string, name: string, from: string, to: string, days: int}>                                                    $discounts
     */
    public function __construct(
        public array $product,
        public array $period,
        public array $months,
        public array $discounts,
        public int $discountedDays,
    ) {
    }
}
