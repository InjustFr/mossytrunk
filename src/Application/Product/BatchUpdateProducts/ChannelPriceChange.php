<?php

declare(strict_types=1);

namespace App\Application\Product\BatchUpdateProducts;

final readonly class ChannelPriceChange
{
    public function __construct(
        public ?string $channelId,
        public ChannelPriceMode $mode,
        public ?int $priceCents = null,
        public ?string $sourceChannelId = null,
        public int $adjustmentCents = 0,
        public int $adjustmentBasisPoints = 0,
    ) {
    }
}
