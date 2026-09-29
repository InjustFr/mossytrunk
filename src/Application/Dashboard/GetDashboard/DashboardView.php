<?php

declare(strict_types=1);

namespace App\Application\Dashboard\GetDashboard;

/**
 * Amounts in cents. Each figure set: orderCount, grossSales, discounts, turnover, costOfGoods, expenses, urssaf, result.
 */
final readonly class DashboardView
{
    /**
     * @param list<int>                                                                      $years    selectable years, most recent first
     * @param list<array<string, int>>                                                       $months   12 entries (month 1–12) of the selected year
     * @param array<string, int>                                                             $total    selected year total
     * @param list<array<string, int>>                                                       $byYear   every year with data, most recent first
     * @param list<array{id: string, name: string, startDate: string, turnover: int, result: int}> $events   events starting in the year, best result first
     * @param list<array{id: string, name: string, typeName: ?string, quantity: int, sales: int}> $products best-selling products of the year, before discounts
     * @param list<array{name: ?string, quantity: int, sales: int}>                          $types    sales per product type of the year, best first
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
    ) {
    }
}
