<?php

declare(strict_types=1);

namespace App\Application\Etsy\UpdateEtsySettings;

use App\Application\Etsy\EtsySession;
use App\Application\Transaction;
use App\Application\Workspace\WorkspaceSecrets;
use App\Application\WorkspaceContext;
use App\Domain\Identity\SecretName;
use App\Domain\Identity\WorkspaceRepository;

final readonly class UpdateEtsySettingsHandler
{
    public function __construct(
        private WorkspaceContext $workspace,
        private WorkspaceRepository $workspaces,
        private WorkspaceSecrets $secrets,
        private EtsySession $session,
        private Transaction $transaction,
    ) {
    }

    public function __invoke(string $keystring, ?string $sharedSecret): void
    {
        $workspace = $this->workspaces->get($this->workspace->current()->id());
        $appChanged = $workspace->configureEtsyApp($keystring);
        if (null !== $sharedSecret && '' !== trim($sharedSecret)) {
            $this->secrets->keep($workspace, SecretName::EtsySharedSecret, trim($sharedSecret));
            $appChanged = true;
        }
        if ($appChanged) {
            $this->session->forget($workspace);
        }

        $this->transaction->commit();
    }
}
