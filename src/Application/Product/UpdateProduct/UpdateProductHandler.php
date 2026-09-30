<?php

declare(strict_types=1);

namespace App\Application\Product\UpdateProduct;

use App\Application\Product\ReferenceAvailability;
use App\Application\Stock\StockKeeper;
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
        private ReferenceAvailability $availability,
        private StockKeeper $stock,
        private Transaction $transaction,
    ) {
    }

    public function __invoke(UpdateProduct $command): void
    {
        $product = $this->products->get(Ulid::fromString($command->productId));

        if (null !== $command->reference) {
            $this->availability->assertAvailable($command->reference, $product);
            $product->changeReference($command->reference);
        }
        $product->rename($command->name);
        $product->reprice(Money::cents($command->sellingPriceCents));
        $product->replaceVariants($command->variants);
        $this->stock->forgetUnsold($product);
        $product->alertBelow($command->lowStockThreshold);
        $product->classify(null === $command->typeId ? null : $this->types->get(Ulid::fromString($command->typeId)));

        $this->transaction->commit();
    }
}
