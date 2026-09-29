<?php

declare(strict_types=1);

namespace App\Application\Etsy\DisconnectEtsy;

use App\Application\Etsy\EtsySession;
use App\Application\Transaction;
use App\Application\WorkspaceContext;
use App\Domain\Identity\WorkspaceRepository;

final readonly class DisconnectEtsyHandler
{
    public function __construct(
        private EtsySession $session,
        private WorkspaceContext $workspace,
        private WorkspaceRepository $workspaces,
        private Transaction $transaction,
    ) {
    }

    public function __invoke(): void
    {
        $this->session->forget($this->workspaces->get($this->workspace->current()->id()));
        $this->transaction->commit();
    }
}
