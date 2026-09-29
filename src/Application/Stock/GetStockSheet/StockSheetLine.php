<?php

declare(strict_types=1);

namespace App\Application\Stock\GetStockSheet;

final readonly class StockSheetLine
{
    public function __construct(
        public string $productId,
        public ?string $variant,
        public string $label,
        public ?string $typeName,
        public int $onHand,
        public int $soldAtEvent,
    ) {
    }
}
