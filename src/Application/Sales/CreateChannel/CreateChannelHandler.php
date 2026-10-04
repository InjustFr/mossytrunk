<?php

declare(strict_types=1);

namespace App\Application\Sales\CreateChannel;

use App\Application\Sales\ChannelAvailability;
use App\Application\Transaction;
use App\Application\WorkspaceContext;
use App\Domain\Sales\ChannelKind;
use App\Domain\Sales\SalesChannel;
use App\Domain\Sales\SalesChannelRepository;
use App\Domain\Shared\OptionalText;
use Symfony\Component\Uid\Ulid;

final readonly class CreateChannelHandler
{
    public function __construct(
        private SalesChannelRepository $channels,
        private ChannelAvailability $availability,
        private WorkspaceContext $workspace,
        private Transaction $transaction,
    ) {
    }

    public function __invoke(string $name, ChannelKind $kind, ?string $service): Ulid
    {
        $service = OptionalText::of($service);
        $this->availability->assertNameFree($name);
        $this->availability->assertServiceFree($service);

        $channel = SalesChannel::open($this->workspace->current(), $name, $kind, $service);
        $this->channels->add($channel);
        $this->transaction->commit();

        return $channel->id();
    }
}
