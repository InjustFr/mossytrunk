<?php

declare(strict_types=1);

namespace App\Application\Product\BatchUpdateProducts;

use App\Domain\Product\Product;
use App\Domain\Sales\PriceAdjustment;
use App\Domain\Sales\SalesChannel;
use App\Domain\Sales\SalesChannelRepository;
use App\Domain\Shared\Money;
use Symfony\Component\Uid\Ulid;

final readonly class ChannelRepricing
{
    private function __construct(
        private ChannelPriceChange $change,
        private ?SalesChannel $target,
        private ?SalesChannel $source,
        private PriceAdjustment $adjustment,
    ) {
    }

    public static function of(ChannelPriceChange $change, SalesChannelRepository $channels): self
    {
        $channel = static fn (?string $id): ?SalesChannel => null === $id ? null : $channels->get(Ulid::fromString($id));
        $adjustment = 0 !== $change->adjustmentBasisPoints
            ? PriceAdjustment::byBasisPoints($change->adjustmentBasisPoints)
            : PriceAdjustment::byCents($change->adjustmentCents);

        return new self($change, $channel($change->channelId), $channel($change->sourceChannelId), $adjustment);
    }

    public function applyTo(Product $product): void
    {
        if (ChannelPriceMode::SellingPrice === $this->change->mode) {
            if (null !== $this->target) {
                $product->followSellingPriceOn($this->target);
            }

            return;
        }

        $price = ChannelPriceMode::Fixed === $this->change->mode
            ? Money::cents($this->change->priceCents ?? 0)
            : $this->adjustment->applyTo($product->priceOn($this->source));

        if (null === $this->target) {
            $product->reprice($price);
        } else {
            $product->setPriceOn($this->target, $price);
        }
    }
}
