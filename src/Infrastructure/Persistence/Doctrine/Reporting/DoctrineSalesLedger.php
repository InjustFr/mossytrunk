<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Reporting;

use App\Application\Reporting\SalesLedger;
use App\Application\WorkspaceContext;
use App\Domain\Reporting\SalesTotals;
use App\Domain\Shared\DateRange;
use App\Domain\Shared\Money;
use Doctrine\DBAL\Connection;
use Symfony\Component\Uid\Ulid;

final readonly class DoctrineSalesLedger implements SalesLedger
{
    public function __construct(
        private Connection $connection,
        private WorkspaceContext $workspace,
    ) {
    }

    public function totalsByEvent(): array
    {
        $totals = [];
        foreach ($this->totals('o.event_id', 'o.event_id IS NOT NULL') as $event => $sales) {
            $totals[(string) Ulid::fromString($event)] = $sales;
        }

        return $totals;
    }

    public function totalsOfEvent(Ulid $eventId): SalesTotals
    {
        return $this->totals('o.event_id', 'o.event_id = :event', ['event' => $eventId->toRfc4122()])[$eventId->toRfc4122()] ?? SalesTotals::zero();
    }

    public function totalsByMonth(): array
    {
        return $this->totals("to_char(o.placed_at AT TIME ZONE :timezone, 'YYYY-MM')", 'TRUE', ['timezone' => DateRange::TIMEZONE]);
    }

    /**
     * @param array<string, string> $parameters
     *
     * @return array<string, SalesTotals>
     */
    private function totals(string $group, string $condition, array $parameters = []): array
    {
        $rows = $this->connection->fetchAllAssociative(
            <<<SQL
                SELECT {$group} AS grouped, COUNT(*) AS orders,
                    SUM(o.subtotal_cents) AS gross, SUM(o.discount_total_cents) AS discounts, SUM(o.shipping_cents) AS shipping,
                    SUM(o.cost_of_goods_cents) AS cost, SUM(o.supplies_cost_cents) AS supplies, SUM(o.channel_costs_cents) AS channel
                FROM "order" o
                WHERE o.workspace_id = :workspace AND o.refunded_at IS NULL AND {$condition}
                GROUP BY grouped
                SQL,
            ['workspace' => $this->workspace->current()->id()->toRfc4122(), ...$parameters],
        );

        $totals = [];
        foreach ($rows as $row) {
            $totals[SqlValue::string($row['grouped'])] = new SalesTotals(
                SqlValue::int($row['orders']),
                Money::cents(SqlValue::int($row['gross'])),
                Money::cents(SqlValue::int($row['discounts'])),
                Money::cents(SqlValue::int($row['shipping'])),
                Money::cents(SqlValue::int($row['cost'])),
                Money::cents(SqlValue::int($row['supplies'])),
                Money::cents(SqlValue::int($row['channel'])),
            );
        }

        return $totals;
    }
}
