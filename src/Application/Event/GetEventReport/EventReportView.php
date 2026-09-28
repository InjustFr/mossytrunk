<?php

declare(strict_types=1);

namespace App\Application\Event\GetEventReport;

use App\Domain\Event\Event;
use App\Domain\Event\Expense;
use App\Domain\Reporting\EventResult;
use App\Domain\Reporting\UrssafContribution;

/**
 * Event report as shown in the UI: Commandes, Dépenses, URSSAF and Total sections. Amounts in cents.
 */
final readonly class EventReportView
{
    /**
     * @param array<string, mixed> $orders
     * @param array<string, mixed> $expenses
     * @param array<string, mixed> $urssaf
     * @param array<string, mixed> $total
     */
    public function __construct(
        public array $orders,
        public array $expenses,
        public array $urssaf,
        public array $total,
    ) {
    }

    /**
     * @param array<string, \App\Domain\Product\Product> $products current products by id, for the recap grouping
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
