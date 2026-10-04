<?php

declare(strict_types=1);

namespace App\Application\Integration;

use App\Application\Workspace\WorkspaceSecrets;
use App\Domain\Identity\SecretName;
use App\Domain\Integration\ServiceConnection;

final readonly class ConnectionSecrets
{
    public function __construct(private WorkspaceSecrets $secrets)
    {
    }

    public function reveal(ServiceConnection $connection, string $field): ?string
    {
        return $this->secrets->reveal($connection->workspace(), SecretName::of($connection->service(), $field));
    }

    public function keep(ServiceConnection $connection, string $field, string $value): void
    {
        $this->secrets->keep($connection->workspace(), SecretName::of($connection->service(), $field), $value);
    }

    public function forget(ServiceConnection $connection, string $field): void
    {
        $this->secrets->forget($connection->workspace(), SecretName::of($connection->service(), $field));
    }
}
