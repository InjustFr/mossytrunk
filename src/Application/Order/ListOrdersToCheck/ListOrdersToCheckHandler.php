<?php

declare(strict_types=1);

namespace App\Application\Order\ListOrdersToCheck;

use App\Domain\Event\EventRepository;
use Symfony\Component\Uid\Ulid;

final readonly class ListOrdersToCheckHandler
{
    public function __construct(
        private EventRepository $events,
        private OrdersToCheck $orders,
    ) {
    }

    /**
     * @return list<OrderToCheckView>
     */
    public function __invoke(string $eventId): array
    {
        return $this->orders->ofEvent($this->events->get(Ulid::fromString($eventId))->id());
    }
}
