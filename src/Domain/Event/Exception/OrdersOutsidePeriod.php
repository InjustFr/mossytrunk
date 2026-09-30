<?php

declare(strict_types=1);

namespace App\Domain\Event\Exception;

final class OrdersOutsidePeriod extends InvalidEvent
{
    public function __construct(int $count)
    {
        parent::__construct(\sprintf('%d commande(s) de cet événement tomberaient en dehors des nouvelles dates.', $count));
    }
}
