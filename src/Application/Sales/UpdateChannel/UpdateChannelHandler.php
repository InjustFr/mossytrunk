<?php

declare(strict_types=1);

namespace App\Application\Sales\UpdateChannel;

use App\Application\Sales\ChannelAvailability;
use App\Application\Transaction;
use App\Domain\Order\OrderRepository;
use App\Domain\Sales\ChannelKind;
use App\Domain\Sales\Exception\ChannelHasOrdersWithoutEvent;
use App\Domain\Sales\SalesChannel;
use App\Domain\Sales\SalesChannelRepository;
use App\Domain\Shared\OptionalText;
use Symfony\Component\Uid\Ulid;

final readonly class UpdateChannelHandler
{
    public function __construct(
        private SalesChannelRepository $channels,
        private ChannelAvailability $availability,
        private OrderRepository $orders,
        private Transaction $transaction,
    ) {
    }

    public function __invoke(string $channelId, string $name, ChannelKind $kind, ?string $service): void
    {
        $service = OptionalText::of($service);
        $channel = $this->channels->get(Ulid::fromString($channelId));
        $this->availability->assertNameFree($name, $channel);
        $this->availability->assertServiceFree($service, $channel);

        $channel->rename($name);
        $channel->linkTo($service);
        $this->changeKind($channel, $kind);
        $this->transaction->commit();
    }

    private function changeKind(SalesChannel $channel, ChannelKind $kind): void
    {
        $channel->changeKind($kind);
        if ($channel->acceptsOrderWithoutEvent()) {
            return;
        }

        $withoutEvent = $this->orders->countWithoutEventOn($channel->id());
        if ($withoutEvent > 0) {
            throw new ChannelHasOrdersWithoutEvent($channel->name(), $withoutEvent);
        }
    }
}
