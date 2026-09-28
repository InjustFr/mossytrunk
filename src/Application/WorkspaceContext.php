<?php

declare(strict_types=1);

namespace App\Application;

use App\Domain\Identity\Workspace;

interface WorkspaceContext
{
    public function current(): Workspace;
}
