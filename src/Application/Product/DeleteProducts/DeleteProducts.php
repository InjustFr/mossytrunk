<?php

declare(strict_types=1);

namespace App\Application\Product\DeleteProducts;

final readonly class DeleteProducts
{
    /**
     * @param list<string> $productIds
     */
    public function __construct(
        public array $productIds,
    ) {
    }
}
