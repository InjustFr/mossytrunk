<?php

declare(strict_types=1);

namespace App\Domain\Stock;

use Symfony\Component\Uid\Ulid;

interface StockCheckRepository
{
    public function add(StockCheck $check): void;

    public function get(Ulid $id): StockCheck;

    /**
     * @return list<StockCheck> most recent first
     */
    public function ofEvent(Ulid $eventId): array;

    /**
     * @return list<StockCheck>
     */
    public function withUnexplainedUnits(): array;
}
