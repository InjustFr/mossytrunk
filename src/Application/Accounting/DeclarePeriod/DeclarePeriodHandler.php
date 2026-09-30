<?php

declare(strict_types=1);

namespace App\Application\Accounting\DeclarePeriod;

use App\Application\Accounting\PeriodTurnover;
use App\Application\Transaction;
use App\Application\WorkspaceContext;
use App\Domain\Accounting\DeclarationPeriod;
use App\Domain\Accounting\UrssafDeclaration;
use App\Domain\Accounting\UrssafDeclarationRepository;
use App\Domain\Order\OrderRepository;
use Psr\Clock\ClockInterface;

final readonly class DeclarePeriodHandler
{
    public function __construct(
        private OrderRepository $orders,
        private UrssafDeclarationRepository $declarations,
        private WorkspaceContext $workspace,
        private ClockInterface $clock,
        private Transaction $transaction,
    ) {
    }

    public function __invoke(string $periodKey): void
    {
        $period = DeclarationPeriod::fromKey($periodKey);
        $previous = $this->declarations->find($period->key());
        if (null !== $previous) {
            $this->declarations->remove($previous);
            $this->transaction->commit();
        }

        $this->declarations->add(UrssafDeclaration::record(
            $this->workspace->current(),
            $period,
            PeriodTurnover::of($period, $this->orders->list())->turnover,
            $this->clock->now(),
        ));
        $this->transaction->commit();
    }
}
