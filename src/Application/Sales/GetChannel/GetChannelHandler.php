<?php

declare(strict_types=1);

namespace App\Application\Sales\GetChannel;

use App\Application\Sales\ChannelViews;
use App\Application\Sales\SalesChannelView;
use App\Domain\Sales\SalesChannelRepository;
use Symfony\Component\Uid\Ulid;

final readonly class GetChannelHandler
{
    public function __construct(
        private SalesChannelRepository $channels,
        private ChannelViews $views,
    ) {
    }

    public function __invoke(string $channelId): SalesChannelView
    {
        return $this->views->of($this->channels->get(Ulid::fromString($channelId)));
    }
}
