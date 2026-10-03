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
     * @return list<Order> orders still counting as sales (not refunded) placed within the period, most recent first
     */
    public function salesWithin(DateRange $period): array;

    public function firstSaleAt(): ?\DateTimeImmutable;

    public function lastSaleAt(): ?\DateTimeImmutable;

    /**
     * @return list<Order> other unrefunded orders of the same event and source, most recent first
     */
    public function mergeCandidatesOf(Order $order): array;

    /**
     * @return list<Order> orders having at least one line of the product
     */
    public function selling(Ulid $productId): array;

    /**
     * @return list<Order> the orders that used the supply
     */
    public function using(Ulid $supplyId): array;

    /**
     * @param list<string> $externalIds
     *
     * @return list<string>
     */
    public function importedExternalIds(string $source, array $externalIds): array;

    /**
     * @return list<Order> orders having a sale imported from the source whose fee the service has not reported yet
     */
    public function awaitingSaleFees(string $source): array;

    /**
     * Number of orders of the event whose date falls outside the given period.
     */
    public function countOutside(Ulid $eventId, DateRange $period): int;

    public function countWithoutEventOn(Ulid $channelId): int;
}
