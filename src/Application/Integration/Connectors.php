<?php

declare(strict_types=1);

namespace App\Application\Integration;

use App\Domain\Order\Order;

final readonly class Connectors
{
    /** @var array<string, SalesConnector> */
    private array $connectors;

    /**
     * @param iterable<SalesConnector> $connectors
     */
    public function __construct(iterable $connectors)
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
        return $this->connectors[$service] ?? throw ServiceUnavailable::unknown($service);
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
            return 'Saisie';
        }

        return isset($this->connectors[$source]) ? $this->connectors[$source]->describe()->label : $source;
    }
}
