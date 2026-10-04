<?php

declare(strict_types=1);

namespace App\Application\Dashboard\GetDashboard;

final readonly class DashboardView
{
    /**
     * @param list<int>                                                                            $years
     * @param list<array<string, int>>                                                             $months
     * @param array<string, int>                                                                   $total
     * @param list<array<string, int>>                                                             $byYear
     * @param list<array{id: string, name: string, startDate: string, turnover: int, result: int}> $events
     * @param list<array{id: string, name: string, typeName: ?string, quantity: int, sales: int}>  $products
     * @param list<array{name: ?string, quantity: int, sales: int}>                                $types
     */
    public function __construct(
        public int $year,
        public array $years,
        public array $months,
        public array $total,
        public array $byYear,
        public array $events,
        public array $products,
        public array $types,
        public int $productsWithoutCost,
        public int $productsLowOnStock = 0,
        public int $productsOutOfStock = 0,
    ) {
    }
}
