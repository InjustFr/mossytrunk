<?php

declare(strict_types=1);

namespace App\Application\Sales\RemoveChannelCost;

use App\Application\Transaction;
use App\Domain\Sales\SalesChannelRepository;
use Symfony\Component\Uid\Ulid;

final readonly class RemoveChannelCostHandler
{
    public function __construct(
        private SalesChannelRepository $channels,
        private Transaction $transaction,
    ) {
    }

    public function __invoke(string $channelId, string $costId): void
    {
        $this->channels->get(Ulid::fromString($channelId))->removeCost(Ulid::fromString($costId));
        $this->transaction->commit();
    }
}
