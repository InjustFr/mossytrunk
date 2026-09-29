<?php

declare(strict_types=1);

namespace App\Application\Product\DeleteProduct;

use App\Application\Transaction;
use App\Domain\Discount\DiscountRuleRepository;
use App\Domain\Product\ProductRepository;
use Symfony\Component\Uid\Ulid;

final readonly class DeleteProductHandler
{
    public function __construct(
        private ProductRepository $products,
        private DiscountRuleRepository $discountRules,
        private Transaction $transaction,
    ) {
    }

    public function __invoke(string $productId): void
    {
        $product = $this->products->get(Ulid::fromString($productId));

        foreach ($this->discountRules->all() as $rule) {
            $rule->withdrawProduct($product);
        }
        $this->products->remove($product);
        $this->transaction->commit();
    }
}
