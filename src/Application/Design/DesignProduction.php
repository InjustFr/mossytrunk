<?php

declare(strict_types=1);

namespace App\Application\Design;

use App\Application\Product\NewProducts;
use App\Domain\Design\Design;
use Psr\Clock\ClockInterface;

final readonly class DesignProduction
{
    public function __construct(
        private NewProducts $newProducts,
        private ClockInterface $clock,
    ) {
    }

    public function produce(Design $design): int
    {
        $declinations = $design->validate($this->clock->now());
        foreach ($declinations as $declination) {
            $product = $this->newProducts->create($declination->productName(), $declination->sellingPrice(), $declination->gabarit()->type(), $declination->variants());
            $declination->linkProduct($product->id());
        }

        return \count($declinations);
    }
}
