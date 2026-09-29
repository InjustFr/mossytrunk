<?php

declare(strict_types=1);

namespace App\Application\Workspace\GetSettings;

final readonly class WorkspaceSettingsView
{
    public function __construct(
        public string $name,
        public SumUpSettingsView $sumUp,
        public EtsySettingsView $etsy,
        public string $declarationPeriodicity,
    ) {
    }
}
