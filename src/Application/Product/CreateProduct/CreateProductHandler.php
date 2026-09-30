<?php

declare(strict_types=1);

namespace App\Application\Product\CreateProduct;

use App\Application\Product\ReferenceAvailability;
use App\Application\Transaction;
use App\Application\WorkspaceContext;
use App\Domain\Product\Product;
use App\Domain\Product\ProductReferenceGenerator;
use App\Domain\Product\ProductRepository;
use App\Domain\Product\ProductType;
use App\Domain\Product\ProductTypeRepository;
use App\Domain\Shared\Money;
use Symfony\Component\Uid\Ulid;

final readonly class CreateProductHandler
{
    public function __construct(
        private ProductRepository $products,
        private ProductTypeRepository $types,
        private ProductReferenceGenerator $references,
        private ReferenceAvailability $availability,
        private Transaction $transaction,
        private WorkspaceContext $workspace,
    ) {
    }

    public function __invoke(CreateProduct $command): Ulid
    {
        $type = null === $command->typeId ? null : $this->types->get(Ulid::fromString($command->typeId));

        $product = Product::create(
            $this->workspace->current(),
            $this->referenceOf($command, $type),
            $command->name,
            Money::cents($command->sellingPriceCents),
            $command->variants,
            $type,
        );

        $product->alertBelow($command->lowStockThreshold);
        $this->products->add($product);
        $this->transaction->commit();

        return $product->id();
    }

    private function referenceOf(CreateProduct $command, ?ProductType $type): string
    {
        if (null === $command->reference || '' === trim($command->reference)) {
            return $this->references->generate($type, $command->name);
        }

        $this->availability->assertAvailable($command->reference);

        return $command->reference;
    }
}
