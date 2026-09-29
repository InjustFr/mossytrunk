<?php

declare(strict_types=1);

namespace App\Domain\Integration;

interface ServiceConnectionRepository
{
    public function add(ServiceConnection $connection): void;

    public function remove(ServiceConnection $connection): void;

    public function find(string $service): ?ServiceConnection;

    public function get(string $service): ServiceConnection;

    /**
     * @return list<ServiceConnection>
     */
    public function all(): array;
}
