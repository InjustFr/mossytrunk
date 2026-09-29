<?php

declare(strict_types=1);

namespace App\Application\Accounting\ChoosePeriodicity;

use App\Application\Transaction;
use App\Application\WorkspaceContext;
use App\Domain\Accounting\DeclarationPeriodicity;
use App\Domain\Identity\WorkspaceRepository;

final readonly class ChoosePeriodicityHandler
{
    public function __construct(
        private WorkspaceContext $workspace,
        private WorkspaceRepository $workspaces,
        private Transaction $transaction,
    ) {
    }

    public function __invoke(DeclarationPeriodicity $periodicity): void
    {
        $this->workspaces->get($this->workspace->current()->id())->declareEvery($periodicity);
        $this->transaction->commit();
    }
}
