<?php

declare(strict_types=1);

namespace App\Application\Design;

use App\Application\Product\ProductReferenceGenerator;
use App\Application\WorkspaceContext;
use App\Domain\Design\Design;
use App\Domain\Product\Product;
use App\Domain\Product\ProductRepository;
use Psr\Clock\ClockInterface;

final readonly class DesignProduction
{
    public function __construct(
        private ProductRepository $products,
        private ProductReferenceGenerator $references,
        private WorkspaceContext $workspace,
        private ClockInterface $clock,
    ) {
    }

    public function produce(Design $design): int
    {
        $declinations = $design->validate($this->clock->now());
        foreach ($declinations as $declination) {
            $type = $declination->gabarit()->type();
            $product = Product::create(
                $this->workspace->current(),
                $this->references->generate($type, $declination->productName()),
                $declination->productName(),
                $declination->sellingPrice(),
                $type,
                $declination->variants(),
            );
            $this->products->add($product);
            $declination->linkProduct($product->id());
        }

        return \count($declinations);
    }
}
