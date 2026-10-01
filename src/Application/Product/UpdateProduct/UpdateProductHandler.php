<?php

declare(strict_types=1);

namespace App\Application\Product\UpdateProduct;

use App\Application\Product\ChannelPricing;
use App\Application\Product\ProductTypeChoice;
use App\Application\Product\ReferenceAvailability;
use App\Application\Stock\StockKeeper;
use App\Application\Transaction;
use App\Domain\Product\ProductRepository;
use App\Domain\Shared\Money;
use Symfony\Component\Uid\Ulid;

final readonly class UpdateProductHandler
{
    public function __construct(
        private ProductRepository $products,
        private ProductTypeChoice $types,
        private ReferenceAvailability $availability,
        private StockKeeper $stock,
        private ChannelPricing $channelPricing,
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
        $this->channelPricing->assign($product, $command->channelPrices);
        $product->replaceVariants($command->variants);
        $this->stock->followVariants($product);
        $product->alertBelow($command->lowStockThreshold);
        $product->classify($this->types->of($command->typeId));
        $product->assertVariantChosen();

        $this->transaction->commit();
    }
}
