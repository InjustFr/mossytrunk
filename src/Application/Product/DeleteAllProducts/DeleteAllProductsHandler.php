<?php

declare(strict_types=1);

namespace App\Application\Product\DeleteAllProducts;

use App\Application\Transaction;
use App\Domain\Discount\DiscountRuleRepository;
use App\Domain\Product\ProductRepository;

final readonly class DeleteAllProductsHandler
{
    public function __construct(
        private ProductRepository $products,
        private DiscountRuleRepository $discountRules,
        private Transaction $transaction,
    ) {
    }

    public function __invoke(): int
    {
        foreach ($this->discountRules->all() as $rule) {
            if ($rule->listsTypes()) {
                $rule->withdrawEveryProduct();
            } else {
                $this->discountRules->remove($rule);
            }
        }
        $products = $this->products->all();
        foreach ($products as $product) {
            $this->products->remove($product);
        }
        $this->transaction->commit();

        return \count($products);
    }
}
