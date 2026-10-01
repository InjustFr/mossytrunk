<?php

declare(strict_types=1);

namespace App\Application\Sales;

use App\Domain\Event\Event;
use App\Domain\Sales\SalesChannel;
use App\Domain\Sales\SalesChannelRepository;

final readonly class OrderChannel
{
    public function __construct(private SalesChannelRepository $channels)
    {
    }

    public function of(?string $service, ?Event $event): ?SalesChannel
    {
        $linked = null === $service ? null : $this->channels->linkedTo($service);

        return $linked ?? (null === $event ? null : $this->channels->main());
    }
}
