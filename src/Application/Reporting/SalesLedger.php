<?php

declare(strict_types=1);

namespace App\Application\Reporting;

use App\Domain\Reporting\SalesTotals;
use Symfony\Component\Uid\Ulid;

interface SalesLedger
{
    /**
     * @return array<string, SalesTotals>
     */
    public function totalsByEvent(): array;

    public function totalsOfEvent(Ulid $eventId): SalesTotals;

    /**
     * @return array<string, SalesTotals>
     */
    public function totalsByMonth(): array;

    public function firstSaleAt(): ?\DateTimeImmutable;

    public function lastSaleAt(): ?\DateTimeImmutable;
}
