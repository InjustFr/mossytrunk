<?php

declare(strict_types=1);

namespace App\Application\Sales\SaveChannel;

use App\Application\Integration\Connectors;
use App\Application\Transaction;
use App\Application\WorkspaceContext;
use App\Domain\Order\OrderRepository;
use App\Domain\Sales\ChannelKind;
use App\Domain\Sales\Exception\ChannelHasOrdersWithoutEvent;
use App\Domain\Sales\Exception\ChannelNameTaken;
use App\Domain\Sales\Exception\ServiceAlreadyLinked;
use App\Domain\Sales\Exception\UnknownSalesService;
use App\Domain\Sales\SalesChannel;
use App\Domain\Sales\SalesChannelRepository;
use Symfony\Component\Uid\Ulid;

final readonly class SaveChannelHandler
{
    public function __construct(
        private SalesChannelRepository $channels,
        private Connectors $connectors,
        private OrderRepository $orders,
        private WorkspaceContext $workspace,
        private Transaction $transaction,
    ) {
    }

    public function __invoke(?string $channelId, string $name, ChannelKind $kind, ?string $service): Ulid
    {
        $service = null === $service || '' === trim($service) ? null : trim($service);
        $channel = null === $channelId ? null : $this->channels->get(Ulid::fromString($channelId));
        $this->assertNameFree($name, $channel);
        $this->assertServiceFree($service, $channel);

        if (null === $channel) {
            $channel = SalesChannel::open($this->workspace->current(), $name, $kind, $service);
            $this->channels->add($channel);
        } else {
            $channel->rename($name);
            $channel->linkTo($service);
            $this->changeKind($channel, $kind);
        }

        $this->transaction->commit();

        return $channel->id();
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

    private function assertNameFree(string $name, ?SalesChannel $saved): void
    {
        foreach ($this->channels->all() as $channel) {
            if ($channel !== $saved && $channel->isNamed($name)) {
                throw new ChannelNameTaken(trim($name));
            }
        }
    }

    private function assertServiceFree(?string $service, ?SalesChannel $saved): void
    {
        if (null === $service) {
            return;
        }
        if (!$this->connectors->has($service)) {
            throw new UnknownSalesService($service);
        }

        $linked = $this->channels->linkedTo($service);
        if (null !== $linked && $linked !== $saved) {
            throw new ServiceAlreadyLinked($this->connectors->labelOf($service), $linked->name());
        }
    }
}
