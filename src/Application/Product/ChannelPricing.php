<?php

declare(strict_types=1);

namespace App\Application\Product;

use App\Domain\Product\Product;
use App\Domain\Sales\SalesChannelRepository;
use App\Domain\Shared\Money;
use Symfony\Component\Uid\Ulid;

final readonly class ChannelPricing
{
    public function __construct(private SalesChannelRepository $channels)
    {
    }

    /**
     * @param array<string, ?int> $pricesByChannel
     */
    public function assign(Product $product, array $pricesByChannel): void
    {
        foreach ($pricesByChannel as $channelId => $cents) {
            $channel = $this->channels->get(Ulid::fromString($channelId));
            if (null === $cents) {
                $product->followSellingPriceOn($channel);
            } else {
                $product->setPriceOn($channel, Money::cents($cents));
            }
        }
    }
}
