<?php

declare(strict_types=1);

namespace App\Application\Workspace\RemoveSumUpApiKey;

use App\Application\Transaction;
use App\Application\Workspace\WorkspaceSecrets;
use App\Application\WorkspaceContext;
use App\Domain\Identity\SecretName;

final readonly class RemoveSumUpApiKeyHandler
{
    public function __construct(
        private WorkspaceContext $workspace,
        private WorkspaceSecrets $secrets,
        private Transaction $transaction,
    ) {
    }

    public function __invoke(): void
    {
        $this->secrets->forget($this->workspace->current(), SecretName::SumUpApiKey);
        $this->transaction->commit();
    }
}
