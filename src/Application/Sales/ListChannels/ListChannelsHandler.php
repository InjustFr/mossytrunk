<?php

declare(strict_types=1);

namespace App\Application\Sales\ListChannels;

use App\Application\Integration\Connectors;
use App\Domain\Sales\SalesChannel;
use App\Domain\Sales\SalesChannelRepository;

final readonly class ListChannelsHandler
{
    public function __construct(
        private SalesChannelRepository $channels,
        private Connectors $connectors,
    ) {
    }

    /**
     * @return list<SalesChannelView>
     */
    public function __invoke(): array
    {
        return array_map(
            fn (SalesChannel $channel): SalesChannelView => new SalesChannelView(
                (string) $channel->id(),
                $channel->name(),
                $channel->service(),
                null === $channel->service() ? null : $this->connectors->labelOf($channel->service()),
                $channel->kind()->value,
                $channel->isMain(),
            ),
            $this->channels->all(),
        );
    }
}
