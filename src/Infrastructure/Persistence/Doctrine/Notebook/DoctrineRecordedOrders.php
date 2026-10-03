<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Notebook;

use App\Application\Notebook\RecordedOrders;
use App\Application\WorkspaceContext;
use App\Domain\Notebook\RecordedLine;
use App\Domain\Notebook\RecordedOrder;
use App\Infrastructure\Persistence\Doctrine\Reporting\SqlValue;
use Doctrine\DBAL\Connection;
use Symfony\Component\Uid\Ulid;

final readonly class DoctrineRecordedOrders implements RecordedOrders
{
    public function __construct(
        private Connection $connection,
        private WorkspaceContext $workspace,
    ) {
    }

    public function ofEvent(Ulid $eventId): array
    {
        $rows = $this->connection->fetchAllAssociative(
            <<<'SQL'
                SELECT o.id, o.reference, o.placed_at, o.refunded_at,
                    l.product_id, l.variant, l.product_name, l.quantity, p.type_id
                FROM "order" o
                JOIN order_line l ON l.order_id = o.id
                LEFT JOIN product p ON p.id = l.product_id
                WHERE o.workspace_id = :workspace AND o.event_id = :event
                ORDER BY o.placed_at, o.id, l.id
                SQL,
            ['workspace' => $this->workspace->current()->id()->toRfc4122(), 'event' => $eventId->toRfc4122()],
        );

        $orders = [];
        $lines = [];
        foreach ($rows as $row) {
            $id = SqlValue::string($row['id']);
            $orders[$id] ??= $row;
            $variant = SqlValue::nullableString($row['variant']);
            $name = SqlValue::string($row['product_name']);
            $lines[$id][] = new RecordedLine(
                null === $variant ? $name : \sprintf('%s — %s', $name, $variant),
                SqlValue::int($row['quantity']),
                SqlValue::ulid($row['product_id']),
                $variant,
                SqlValue::ulid($row['type_id']),
            );
        }

        return array_values(array_map(static fn (array $order): RecordedOrder => new RecordedOrder(
            Ulid::fromString(SqlValue::string($order['id'])),
            SqlValue::string($order['reference']),
            new \DateTimeImmutable(SqlValue::string($order['placed_at'])),
            $lines[SqlValue::string($order['id'])],
            null !== $order['refunded_at'],
        ), $orders));
    }
}
