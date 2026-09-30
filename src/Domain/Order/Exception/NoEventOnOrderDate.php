<?php

declare(strict_types=1);

namespace App\Domain\Order\Exception;

final class NoEventOnOrderDate extends InvalidOrder
{
    public function __construct(\DateTimeImmutable $placedAt)
    {
        parent::__construct('order.no_event_on_date', ['date' => $placedAt]);
    }
}
