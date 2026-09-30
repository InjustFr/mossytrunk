<?php

declare(strict_types=1);

namespace App\Application\Design;

use App\Domain\Design\DesignRepository;
use App\Domain\Design\Exception\ProductAlreadyDesigned;
use App\Domain\Product\Product;
use App\Domain\Product\ProductRepository;
use Symfony\Component\Uid\Ulid;

final readonly class UndesignedProducts
{
    public function __construct(
        private ProductRepository $products,
        private DesignRepository $designs,
    ) {
    }

    public function get(string $productId): Product
    {
        $product = $this->products->get(Ulid::fromString($productId));
        $existing = $this->designs->findByProduct($product->id());
        if (null !== $existing) {
            throw new ProductAlreadyDesigned($product->displayName(), $existing->name());
        }

        return $product;
    }
}
