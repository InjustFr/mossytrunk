<?php

declare(strict_types=1);

namespace App\Domain\Order\Exception;

final class OrderOutsideEvent extends InvalidOrder
{
    public function __construct(string $eventName, \DateTimeImmutable $placedAt)
    {
        parent::__construct('order.outside_event', ['date' => $placedAt, 'event' => $eventName]);
    }
}
