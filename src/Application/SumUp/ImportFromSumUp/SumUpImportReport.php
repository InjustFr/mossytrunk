<?php

declare(strict_types=1);

namespace App\Application\SumUp\ImportFromSumUp;

final readonly class SumUpImportReport
{
    /**
     * @param list<string> $datesWithoutEvent  YYYY-MM-DD (Paris), sorted, unique
     * @param list<string> $unresolvedProducts SumUp line names matching a product with variants but no known variant
     */
    public function __construct(
        public int $productsCreated,
        public int $ordersImported,
        public int $ordersAlreadyImported,
        public int $ordersWithoutEvent,
        public array $datesWithoutEvent,
        public int $ordersWithUnresolvedProducts,
        public array $unresolvedProducts,
    ) {
    }
}
