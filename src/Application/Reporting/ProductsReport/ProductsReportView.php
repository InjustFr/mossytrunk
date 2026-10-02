<?php

declare(strict_types=1);

namespace App\Application\Reporting\ProductsReport;

final readonly class ProductsReportView
{
    /**
     * @param array{choice: string, from: string, to: string}                                                                                                                                      $period
     * @param list<int>                                                                                                                                                                            $years
     * @param list<array{id: string, name: string, typeId: string, typeName: string, units: int, gross: int, revenue: int, discount: int, cost: int, margin: int, unknownCost: bool, onHand: int}> $products
     * @param array{units: int, gross: int, revenue: int, discount: int, margin: int}                                                                                                              $totals
     */
    public function __construct(
        public array $period,
        public array $years,
        public array $products,
        public array $totals,
    ) {
    }
}
