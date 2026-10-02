<?php

declare(strict_types=1);

namespace App\Application\Sales\OfferSupplies;

use App\Application\Transaction;
use App\Domain\Product\Product;
use App\Domain\Product\ProductRepository;
use App\Domain\Sales\SalesChannelRepository;
use Symfony\Component\Uid\Ulid;

final readonly class OfferSuppliesHandler
{
    public function __construct(
        private SalesChannelRepository $channels,
        private ProductRepository $products,
        private Transaction $transaction,
    ) {
    }

    /**
     * @param list<string> $supplyIds
     */
    public function __invoke(string $channelId, array $supplyIds): void
    {
        $this->channels->get(Ulid::fromString($channelId))->offerSupplies(array_map(fn (string $id): Product => $this->products->get(Ulid::fromString($id)), array_values(array_unique($supplyIds))));
        $this->transaction->commit();
    }
}
