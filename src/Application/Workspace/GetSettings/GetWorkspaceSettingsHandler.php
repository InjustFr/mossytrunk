<?php

declare(strict_types=1);

namespace App\Application\Workspace\GetSettings;

use App\Application\Workspace\WorkspaceSecrets;
use App\Application\WorkspaceContext;
use App\Domain\Identity\SecretName;

final readonly class GetWorkspaceSettingsHandler
{
    public function __construct(
        private WorkspaceContext $workspace,
        private WorkspaceSecrets $secrets,
    ) {
    }

    public function __invoke(): WorkspaceSettingsView
    {
        $workspace = $this->workspace->current();

        return new WorkspaceSettingsView(
            $workspace->name(),
            SumUpSettingsView::of($workspace->sumUpMerchantCode(), $this->secrets->reveal($workspace, SecretName::SumUpApiKey)),
            EtsySettingsView::of($workspace->etsyKeystring(), $this->secrets->reveal($workspace, SecretName::EtsySharedSecret), null === $workspace->etsyShopId() ? null : $workspace->etsyShopName()),
            $workspace->declarationPeriodicity()->value,
        );
    }
}
