<?php

declare(strict_types=1);

namespace App\Domain\Order;

use App\Domain\Shared\DateRange;
use Symfony\Component\Uid\Ulid;

interface OrderRepository
{
    public function add(Order $order): void;

    public function remove(Order $order): void;

    /**
     * @throws \App\Domain\Shared\NotFound
     */
    public function get(Ulid $id): Order;

    /**
     * @return list<Order> most recent first, optionally restricted to one event
     */
    public function list(?Ulid $eventId = null): array;

    /**
     * @param list<string> $transactionCodes
     *
     * @return list<string> the codes already imported
     */
    public function importedSumUpTransactionCodes(array $transactionCodes): array;

    /**
     * Number of orders of the event whose date falls outside the given period.
     */
    public function countOutside(Ulid $eventId, DateRange $period): int;
}
