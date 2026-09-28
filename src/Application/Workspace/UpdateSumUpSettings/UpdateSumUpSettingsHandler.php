<?php

declare(strict_types=1);

namespace App\Application\Workspace\UpdateSumUpSettings;

use App\Application\Transaction;
use App\Application\Workspace\WorkspaceSecrets;
use App\Application\WorkspaceContext;
use App\Domain\Identity\SecretName;

final readonly class UpdateSumUpSettingsHandler
{
    public function __construct(
        private WorkspaceContext $workspace,
        private WorkspaceSecrets $secrets,
        private Transaction $transaction,
    ) {
    }

    public function __invoke(UpdateSumUpSettings $command): void
    {
        $workspace = $this->workspace->current();
        $workspace->configureSumUp($command->merchantCode);

        if (null !== $command->apiKey && '' !== trim($command->apiKey)) {
            $this->secrets->keep($workspace, SecretName::SumUpApiKey, $command->apiKey);
        }

        $this->transaction->commit();
    }
}
