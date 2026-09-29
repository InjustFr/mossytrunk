<?php

declare(strict_types=1);

namespace App\Application\Accounting\ExportOrders;

final readonly class OrdersCsv
{
    public function __construct(
        public string $filename,
        public string $content,
        public int $orderCount,
    ) {
    }
}
