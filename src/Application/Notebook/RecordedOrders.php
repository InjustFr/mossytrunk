<?php

declare(strict_types=1);

namespace App\Application\Notebook;

use App\Domain\Notebook\RecordedOrder;
use Symfony\Component\Uid\Ulid;

interface RecordedOrders
{
    /**
     * @return list<RecordedOrder>
     */
    public function ofEvent(Ulid $eventId): array;
}
