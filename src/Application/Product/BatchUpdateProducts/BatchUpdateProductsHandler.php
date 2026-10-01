<?php

declare(strict_types=1);

namespace App\Application\Product\BatchUpdateProducts;

use App\Application\Product\ProductTypeChoice;
use App\Application\Stock\StockKeeper;
use App\Application\Transaction;
use App\Domain\Product\ProductRepository;
use App\Domain\Sales\SalesChannelRepository;
use App\Domain\Shared\Money;
use Symfony\Component\Uid\Ulid;

/**
 * Batch edit (e.g. price of all stickers, add A3 to every print). Every change goes through the
 * entity methods, so the usual rules apply; any violation aborts the whole batch (nothing is saved).
 */
final readonly class BatchUpdateProductsHandler
{
    public function __construct(
        private ProductRepository $products,
        private ProductTypeChoice $types,
        private StockKeeper $stock,
        private SalesChannelRepository $channels,
        private Transaction $transaction,
    ) {
    }

    public function __invoke(BatchUpdateProducts $command): int
    {
        $type = $command->changeType ? $this->types->of($command->typeId) : null;
        $repricing = null === $command->channelPrice ? null : ChannelRepricing::of($command->channelPrice, $this->channels);

        $ids = array_values(array_unique($command->productIds));
        foreach ($ids as $id) {
            $product = $this->products->get(Ulid::fromString($id));

            if (null !== $command->sellingPriceCents) {
                $product->reprice(Money::cents($command->sellingPriceCents));
            }
            $repricing?->applyTo($product);
            if (null !== $command->lowStockThreshold) {
                $product->alertBelow($command->lowStockThreshold);
            }
            if (null !== $type) {
                $product->classify($type);
            }
            foreach ($command->removeVariants as $variant) {
                $product->removeVariant(trim($variant));
            }
            foreach ($command->addVariants as $variant) {
                if (!$product->hasVariant(trim($variant))) {
                    $product->addVariant($variant);
                }
            }
            $product->assertVariantChosen();
            $this->stock->followVariants($product);
        }

        $this->transaction->commit();

        return \count($ids);
    }
}
