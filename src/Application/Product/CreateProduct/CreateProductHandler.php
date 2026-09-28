<?php

declare(strict_types=1);

namespace App\Application\Product\CreateProduct;

use App\Application\Transaction;
use App\Domain\Product\InvalidProduct;
use App\Domain\Product\Product;
use App\Domain\Product\ProductRepository;
use App\Domain\Shared\Money;
use Symfony\Component\Uid\Ulid;

final readonly class CreateProductHandler
{
    public function __construct(
        private ProductRepository $products,
        private Transaction $transaction,
    ) {
    }

    public function __invoke(CreateProduct $command): Ulid
    {
        if (null !== $this->products->findByReference(trim($command->reference))) {
            throw InvalidProduct::referenceAlreadyUsed(trim($command->reference));
        }

        $product = Product::create(
            $command->reference,
            $command->name,
            Money::cents($command->sellingPriceCents),
            Money::cents($command->buyingPriceCents),
            $command->variants,
        );

        $this->products->add($product);
        $this->transaction->commit();

        return $product->id();
    }
}
