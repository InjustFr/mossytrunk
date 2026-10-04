<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Reporting;

use App\Application\Integration\Connectors;
use App\Application\Order\ListOrdersToCheck\OrdersToCheck;
use App\Application\Order\ListOrdersToCheck\OrderToCheckView;
use App\Application\WorkspaceContext;
use App\Domain\Shared\BusinessTime;
use Doctrine\DBAL\Connection;
use Symfony\Component\Uid\Ulid;

final readonly class DoctrineOrdersToCheck implements OrdersToCheck
{
    public function __construct(
        private Connection $connection,
        private WorkspaceContext $workspace,
        private Connectors $connectors,
    ) {
    }

    public function ofEvent(Ulid $eventId): array
    {
        $rows = $this->connection->fetchAllAssociative(
            <<<'SQL'
                SELECT o.id, o.reference, o.placed_at, o.source, o.payment_method, o.total_cents,
                    o.refunded_at IS NOT NULL AS refunded, o.checked_at IS NOT NULL AS checked, l.lines
                FROM "order" o
                LEFT JOIN LATERAL (
                    SELECT JSON_AGG(JSON_BUILD_OBJECT(
                        'label', CASE WHEN variant IS NULL THEN product_name ELSE product_name || ' — ' || variant END,
                        'quantity', quantity,
                        'unidentified', product_id IS NULL
                    ) ORDER BY id) AS lines
                    FROM order_line WHERE order_id = o.id
                ) l ON TRUE
                WHERE o.workspace_id = :workspace AND o.event_id = :event
                ORDER BY o.placed_at, o.id
                SQL,
            ['workspace' => $this->workspace->current()->id()->toRfc4122(), 'event' => $eventId->toRfc4122()],
        );

        return array_map($this->view(...), $rows);
    }

    /**
     * @param array<string, mixed> $row
     */
    private function view(array $row): OrderToCheckView
    {
        $source = SqlValue::string($row['source']);
        $lines = json_decode(SqlValue::string($row['lines']) ?: '[]', true);

        return new OrderToCheckView(
            (string) SqlValue::ulid($row['id']),
            SqlValue::string($row['reference']),
            BusinessTime::atom(new \DateTimeImmutable(SqlValue::string($row['placed_at']))),
            $source,
            $this->connectors->labelOf($source),
            SqlValue::nullableString($row['payment_method']),
            SqlValue::int($row['total_cents']),
            SqlValue::bool($row['refunded']),
            SqlValue::bool($row['checked']),
            array_map(static fn (mixed $line): array => [
                'label' => SqlValue::string(\is_array($line) ? ($line['label'] ?? '') : ''),
                'quantity' => SqlValue::int(\is_array($line) ? ($line['quantity'] ?? 0) : 0),
                'unidentified' => SqlValue::bool(\is_array($line) ? ($line['unidentified'] ?? false) : false),
            ], \is_array($lines) ? array_values($lines) : []),
        );
    }
}
