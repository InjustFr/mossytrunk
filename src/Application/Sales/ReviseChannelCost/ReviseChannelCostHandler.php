<?php

declare(strict_types=1);

namespace App\Application\Sales\ReviseChannelCost;

use App\Application\Transaction;
use App\Domain\Sales\ChannelCostKind;
use App\Domain\Sales\SalesChannelRepository;
use Symfony\Component\Uid\Ulid;

final readonly class ReviseChannelCostHandler
{
    public function __construct(
        private SalesChannelRepository $channels,
        private Transaction $transaction,
    ) {
    }

    public function __invoke(string $channelId, string $costId, string $label, ChannelCostKind $kind, int $amount): void
    {
        $this->channels->get(Ulid::fromString($channelId))->reviseCost(Ulid::fromString($costId), $label, $kind, $amount);
        $this->transaction->commit();
    }
}
