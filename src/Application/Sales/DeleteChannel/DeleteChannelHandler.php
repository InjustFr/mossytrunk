<?php

declare(strict_types=1);

namespace App\Application\Sales\DeleteChannel;

use App\Application\Transaction;
use App\Domain\Sales\SalesChannelRepository;
use Symfony\Component\Uid\Ulid;

final readonly class DeleteChannelHandler
{
    public function __construct(
        private SalesChannelRepository $channels,
        private Transaction $transaction,
    ) {
    }

    public function __invoke(string $channelId): void
    {
        $channel = $this->channels->get(Ulid::fromString($channelId));
        $channel->assertRemovable();
        $this->channels->remove($channel);
        $this->transaction->commit();
    }
}
