<?php

declare(strict_types=1);

namespace App\Application\Integration;

use App\Application\Integration\Exception\ServiceNotAdded;
use App\Domain\Integration\ServiceConnection;
use App\Domain\Integration\ServiceConnectionRepository;

final readonly class AddedConnection
{
    public function __construct(private ServiceConnectionRepository $connections)
    {
    }

    public function of(SalesConnector $connector): ServiceConnection
    {
        $description = $connector->describe();

        return $this->connections->find($description->key) ?? throw new ServiceNotAdded($description->label);
    }
}
