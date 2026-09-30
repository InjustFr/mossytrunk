<?php

declare(strict_types=1);

namespace App\Domain\Design\Exception;

final class ProductAlreadyDesigned extends InvalidDesign
{
    public function __construct(string $product, string $design)
    {
        parent::__construct('design.product_already_designed', ['product' => $product, 'design' => $design]);
    }
}
