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
     * @throws \App\Domain\Shared\Exception\NotFound
     */
    public function get(Ulid $id): Order;

    /**
     * @return list<Order> most recent first, optionally restricted to one event
     */
    public function list(?Ulid $eventId = null): array;

    /**
     * @return list<Order> orders still counting as sales (not refunded), most recent first, optionally restricted to one event
     */
    public function sales(?Ulid $eventId = null): array;

    /**
     * @return list<Order> orders having at least one line of the product
     */
    public function selling(Ulid $productId): array;

    /**
     * @param list<string> $externalIds
     *
     * @return list<string>
     */
    public function importedExternalIds(string $source, array $externalIds): array;

    /**
     * Number of orders of the event whose date falls outside the given period.
     */
    public function countOutside(Ulid $eventId, DateRange $period): int;
}
