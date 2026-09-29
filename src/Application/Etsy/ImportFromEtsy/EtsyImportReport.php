<?php

declare(strict_types=1);

namespace App\Application\Etsy\ImportFromEtsy;

final readonly class EtsyImportReport
{
    public function __construct(
        public int $ordersImported,
        public int $ordersAlreadyImported,
        public int $ordersWaitingForListings,
        public int $listingsToLink,
    ) {
    }
}
