<?php

declare(strict_types=1);

namespace App\Application\Product\CreateProduct;

use App\Application\Transaction;
use App\Domain\Product\Product;
use App\Domain\Product\ProductReferenceGenerator;
use App\Domain\Product\ProductRepository;
use App\Domain\Product\ProductTypeRepository;
use App\Domain\Shared\Money;
use Symfony\Component\Uid\Ulid;

final readonly class CreateProductHandler
{
    public function __construct(
        private ProductRepository $products,
        private ProductTypeRepository $types,
        private ProductReferenceGenerator $references,
        private Transaction $transaction,
    ) {
    }

    public function __invoke(CreateProduct $command): Ulid
    {
        $type = null === $command->typeId ? null : $this->types->get(Ulid::fromString($command->typeId));

        $product = Product::create(
            $this->references->generate($type, $command->name),
            $command->name,
            Money::cents($command->sellingPriceCents),
            Money::cents($command->buyingPriceCents),
            $command->variants,
            $type,
        );

        $this->products->add($product);
        $this->transaction->commit();

        return $product->id();
    }
}
