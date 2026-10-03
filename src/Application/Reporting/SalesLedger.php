<?php

declare(strict_types=1);

namespace App\Application\Reporting;

use App\Domain\Reporting\SalesTotals;
use Symfony\Component\Uid\Ulid;

/**
 * Totals of the figures recorded by the orders still counting as sales (not refunded).
 */
interface SalesLedger
{
    /**
     * @return array<string, SalesTotals> keyed by event id
     */
    public function totalsByEvent(): array;

    public function totalsOfEvent(Ulid $eventId): SalesTotals;

    /**
     * @return array<string, SalesTotals> keyed "YYYY-MM" (Europe/Paris)
     */
    public function totalsByMonth(): array;
}
