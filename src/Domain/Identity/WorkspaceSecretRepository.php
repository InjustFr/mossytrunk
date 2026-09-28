<?php

declare(strict_types=1);

namespace App\Domain\Identity;

interface WorkspaceSecretRepository
{
    public function add(WorkspaceSecret $secret): void;

    public function remove(WorkspaceSecret $secret): void;

    public function find(Workspace $workspace, SecretName $name): ?WorkspaceSecret;
}
