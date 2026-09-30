<?php

declare(strict_types=1);

namespace App\Application\Integration;

use App\Application\Integration\Exception\ServiceNotAuthorizing;
use App\Application\Integration\Exception\ServiceNotRegistered;
use App\Application\Translator;
use App\Domain\Order\Order;

final readonly class Connectors
{
    /** @var array<string, SalesConnector> */
    private array $connectors;

    /**
     * @param iterable<SalesConnector> $connectors
     */
    public function __construct(iterable $connectors, private Translator $translator)
    {
        $byKey = [];
        foreach ($connectors as $connector) {
            $byKey[$connector->describe()->key] = $connector;
        }
        ksort($byKey);
        $this->connectors = $byKey;
    }

    public function get(string $service): SalesConnector
    {
        return $this->connectors[$service] ?? throw new ServiceNotRegistered($service);
    }

    public function authorizing(string $service): AuthorizingConnector
    {
        $connector = $this->get($service);
        if (!$connector instanceof AuthorizingConnector) {
            throw new ServiceNotAuthorizing($connector->describe()->label);
        }

        return $connector;
    }

    public function has(string $service): bool
    {
        return isset($this->connectors[$service]);
    }

    /**
     * @return list<SalesConnector>
     */
    public function all(): array
    {
        return array_values($this->connectors);
    }

    public function labelOf(string $source): string
    {
        if (Order::MANUAL === $source) {
            return $this->translator->trans('order.source.manual');
        }

        return isset($this->connectors[$source]) ? $this->connectors[$source]->describe()->label : $source;
    }
}
