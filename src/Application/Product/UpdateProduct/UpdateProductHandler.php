<?php

declare(strict_types=1);

namespace App\Application\Product\UpdateProduct;

use App\Application\Transaction;
use App\Domain\Product\InvalidProduct;
use App\Domain\Product\ProductRepository;
use App\Domain\Shared\Money;
use Symfony\Component\Uid\Ulid;

final readonly class UpdateProductHandler
{
    public function __construct(
        private ProductRepository $products,
        private Transaction $transaction,
    ) {
    }

    public function __invoke(UpdateProduct $command): void
    {
        $product = $this->products->get(Ulid::fromString($command->productId));

        $sameReference = $this->products->findByReference(trim($command->reference));
        if (null !== $sameReference && !$sameReference->id()->equals($product->id())) {
            throw InvalidProduct::referenceAlreadyUsed(trim($command->reference));
        }

        $product->describe($command->reference, $command->name);
        $product->reprice(Money::cents($command->sellingPriceCents), Money::cents($command->buyingPriceCents));
        $product->replaceVariants($command->variants);

        $this->transaction->commit();
    }
}
