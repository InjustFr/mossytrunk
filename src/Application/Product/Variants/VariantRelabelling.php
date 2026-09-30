<?php

declare(strict_types=1);

namespace App\Application\Product\Variants;

use Symfony\Component\Uid\Ulid;

interface VariantRelabelling
{
    /**
     * @param list<Ulid> $productIds
     */
    public function relabel(array $productIds, string $from, string $to): void;
}
