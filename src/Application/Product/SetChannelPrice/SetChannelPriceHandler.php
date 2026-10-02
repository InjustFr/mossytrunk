<?php

declare(strict_types=1);

namespace App\Application\Product\SetChannelPrice;

use App\Application\Product\ChannelPricing;
use App\Application\Transaction;
use App\Domain\Product\ProductRepository;
use Symfony\Component\Uid\Ulid;

final readonly class SetChannelPriceHandler
{
    public function __construct(
        private ProductRepository $products,
        private ChannelPricing $pricing,
        private Transaction $transaction,
    ) {
    }

    public function __invoke(string $productId, string $channelId, ?int $priceCents): void
    {
        $this->pricing->assign($this->products->get(Ulid::fromString($productId)), [$channelId => $priceCents]);
        $this->transaction->commit();
    }
}
