<?php

declare(strict_types=1);

namespace App\Application\Product\UpdateProduct;

use App\Application\Transaction;
use App\Domain\Product\ProductRepository;
use App\Domain\Product\ProductTypeRepository;
use App\Domain\Shared\Money;
use Symfony\Component\Uid\Ulid;

final readonly class UpdateProductHandler
{
    public function __construct(
        private ProductRepository $products,
        private ProductTypeRepository $types,
        private Transaction $transaction,
    ) {
    }

    public function __invoke(UpdateProduct $command): void
    {
        $product = $this->products->get(Ulid::fromString($command->productId));

        $product->rename($command->name);
        $product->reprice(Money::cents($command->sellingPriceCents), Money::cents($command->buyingPriceCents));
        $product->replaceVariants($command->variants);
        $product->classify(null === $command->typeId ? null : $this->types->get(Ulid::fromString($command->typeId)));

        $this->transaction->commit();
    }
}
