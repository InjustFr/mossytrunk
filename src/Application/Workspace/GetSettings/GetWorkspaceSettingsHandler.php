<?php

declare(strict_types=1);

namespace App\Application\Workspace\GetSettings;

use App\Application\WorkspaceContext;

final readonly class GetWorkspaceSettingsHandler
{
    public function __construct(private WorkspaceContext $workspace)
    {
    }

    public function __invoke(): WorkspaceSettingsView
    {
        $workspace = $this->workspace->current();

        return new WorkspaceSettingsView($workspace->name(), $workspace->declarationPeriodicity()->value);
    }
}
