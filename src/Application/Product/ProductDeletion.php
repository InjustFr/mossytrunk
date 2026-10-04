<?php

declare(strict_types=1);

namespace App\Application\Product;

use App\Domain\Discount\DiscountRuleRepository;
use App\Domain\Product\Product;
use App\Domain\Product\ProductRepository;

final readonly class ProductDeletion
{
    public function __construct(
        private ProductRepository $products,
        private DiscountRuleRepository $discountRules,
    ) {
    }

    public function delete(Product ...$products): void
    {
        $rules = $this->discountRules->all();
        foreach ($products as $product) {
            foreach ($rules as $rule) {
                $rule->withdrawProduct($product);
            }
            $this->products->remove($product);
        }
    }
}
