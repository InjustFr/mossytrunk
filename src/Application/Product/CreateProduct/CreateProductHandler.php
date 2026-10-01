<?php

declare(strict_types=1);

namespace App\Application\Product\CreateProduct;

use App\Application\Product\ChannelPricing;
use App\Application\Product\ProductReferenceGenerator;
use App\Application\Product\ProductTypeChoice;
use App\Application\Product\ReferenceAvailability;
use App\Application\Transaction;
use App\Application\WorkspaceContext;
use App\Domain\Product\Product;
use App\Domain\Product\ProductRepository;
use App\Domain\Product\ProductType;
use App\Domain\Shared\Money;
use Symfony\Component\Uid\Ulid;

final readonly class CreateProductHandler
{
    public function __construct(
        private ProductRepository $products,
        private ProductTypeChoice $types,
        private ProductReferenceGenerator $references,
        private ReferenceAvailability $availability,
        private Transaction $transaction,
        private WorkspaceContext $workspace,
        private ChannelPricing $channelPricing,
    ) {
    }

    public function __invoke(CreateProduct $command): Ulid
    {
        $type = $this->types->of($command->typeId);

        $product = Product::create(
            $this->workspace->current(),
            $this->referenceOf($command, $type),
            $command->name,
            Money::cents($command->sellingPriceCents),
            $type,
            $command->variants,
        );

        $product->assertVariantChosen();
        $product->alertBelow($command->lowStockThreshold);
        $this->channelPricing->assign($product, $command->channelPrices);
        $this->products->add($product);
        $this->transaction->commit();

        return $product->id();
    }

    private function referenceOf(CreateProduct $command, ProductType $type): string
    {
        if (null === $command->reference || '' === trim($command->reference)) {
            return $this->references->generate($type, $command->name);
        }

        $this->availability->assertAvailable($command->reference);

        return $command->reference;
    }
}
