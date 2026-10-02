<?php

declare(strict_types=1);

namespace App\Application\Sales;

use App\Application\Integration\Connectors;
use App\Domain\Sales\SalesChannel;

final readonly class ChannelViews
{
    public function __construct(private Connectors $connectors)
    {
    }

    public function of(SalesChannel $channel): SalesChannelView
    {
        return new SalesChannelView(
            (string) $channel->id(),
            $channel->name(),
            $channel->service(),
            null === $channel->service() ? null : $this->connectors->labelOf($channel->service()),
            $channel->kind()->value,
            $channel->isMain(),
        );
    }
}
