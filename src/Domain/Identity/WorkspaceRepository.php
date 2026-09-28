<?php

declare(strict_types=1);

namespace App\Domain\Identity;

use Symfony\Component\Uid\Ulid;

interface WorkspaceRepository
{
    public function add(Workspace $workspace): void;

    public function get(Ulid $id): Workspace;

    public function findByName(string $name): ?Workspace;
}
