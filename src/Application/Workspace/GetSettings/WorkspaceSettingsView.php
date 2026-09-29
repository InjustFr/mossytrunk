<?php

declare(strict_types=1);

namespace App\Application\Workspace\GetSettings;

final readonly class WorkspaceSettingsView
{
    public function __construct(
        public string $name,
        public string $declarationPeriodicity,
    ) {
    }
}
