<?php

declare(strict_types=1);

namespace App\Application\Sales;

use App\Application\Integration\Connectors;
use App\Domain\Sales\Exception\ChannelNameTaken;
use App\Domain\Sales\Exception\ServiceAlreadyLinked;
use App\Domain\Sales\Exception\UnknownSalesService;
use App\Domain\Sales\SalesChannel;
use App\Domain\Sales\SalesChannelRepository;

final readonly class ChannelAvailability
{
    public function __construct(
        private SalesChannelRepository $channels,
        private Connectors $connectors,
    ) {
    }

    public function assertNameFree(string $name, ?SalesChannel $owner = null): void
    {
        foreach ($this->channels->all() as $channel) {
            if ($channel !== $owner && $channel->isNamed($name)) {
                throw new ChannelNameTaken(trim($name));
            }
        }
    }

    public function assertServiceFree(?string $service, ?SalesChannel $owner = null): void
    {
        if (null === $service) {
            return;
        }
        if (!$this->connectors->has($service)) {
            throw new UnknownSalesService($service);
        }

        $linked = $this->channels->linkedTo($service);
        if (null !== $linked && $linked !== $owner) {
            throw new ServiceAlreadyLinked($this->connectors->labelOf($service), $linked->name());
        }
    }
}
