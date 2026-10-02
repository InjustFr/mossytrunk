<?php

declare(strict_types=1);

namespace App\Application\Sales\AddChannelCost;

use App\Application\Transaction;
use App\Domain\Sales\ChannelCostKind;
use App\Domain\Sales\SalesChannelRepository;
use Symfony\Component\Uid\Ulid;

final readonly class AddChannelCostHandler
{
    public function __construct(
        private SalesChannelRepository $channels,
        private Transaction $transaction,
    ) {
    }

    public function __invoke(string $channelId, string $label, ChannelCostKind $kind, int $amount): Ulid
    {
        $cost = $this->channels->get(Ulid::fromString($channelId))->addCost($label, $kind, $amount);
        $this->transaction->commit();

        return $cost->id();
    }
}
