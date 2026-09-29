<?php

declare(strict_types=1);

namespace App\Application\Event\GetEventReport;

use App\Domain\Event\Event;
use App\Domain\Event\Expense;
use App\Domain\Product\Product;
use App\Domain\Reporting\EventResult;
use App\Domain\Reporting\UrssafContribution;

/**
 * Event report as shown in the UI: Commandes, Dépenses, URSSAF and Total sections. Amounts in cents.
 */
final readonly class EventReportView
{
    /**
     * @param array{count: int, grossSales: int, discounts: int, turnover: int, costOfGoods: int, groups: list<array{type: string, products: list<array{name: string, variants: list<array{variant: string, quantity: int, sales: int, cost: int, unknownCost: bool}>, quantity: int, sales: int, cost: int, unknownCost: bool}>, quantity: int, sales: int, cost: int, unknownCost: bool}>} $orders
     * @param array{items: list<array{label: string, amount: int}>, total: int}                                          $expenses
     * @param array{rate: int|float, base: int, amount: int}                                                                $urssaf
     * @param array{turnover: int, costOfGoods: int, expenses: int, urssaf: int, result: int}                                $total
     */
    public function __construct(
        public array $orders,
        public array $expenses,
        public array $urssaf,
        public array $total,
    ) {
    }

    /**
     * @param array<string, Product> $products
     */
    public static function of(Event $event, EventResult $result, array $products = []): self
    {
        return new self(
            orders: [
                'count' => $result->orderCount,
                'grossSales' => $result->grossSales->amount(),
                'discounts' => $result->discounts->amount(),
                'turnover' => $result->turnover->amount(),
                'costOfGoods' => $result->costOfGoods->amount(),
                'groups' => OrderRecap::group($result->productSales, $products),
            ],
            expenses: [
                'items' => array_map(static fn (Expense $expense): array => ['label' => $expense->label(), 'amount' => $expense->amount()->amount()], $event->expenses()),
                'total' => $result->expenses->amount(),
            ],
            urssaf: [
                'rate' => UrssafContribution::RATE_BASIS_POINTS / 100,
                'base' => $result->turnover->amount(),
                'amount' => $result->urssaf->amount(),
            ],
            total: [
                'turnover' => $result->turnover->amount(),
                'costOfGoods' => $result->costOfGoods->amount(),
                'expenses' => $result->expenses->amount(),
                'urssaf' => $result->urssaf->amount(),
                'result' => $result->result->amount(),
            ],
        );
    }
}
