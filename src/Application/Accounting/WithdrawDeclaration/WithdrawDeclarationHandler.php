<?php

declare(strict_types=1);

namespace App\Application\Accounting\WithdrawDeclaration;

use App\Application\Transaction;
use App\Domain\Accounting\DeclarationPeriod;
use App\Domain\Accounting\UrssafDeclarationRepository;

final readonly class WithdrawDeclarationHandler
{
    public function __construct(
        private UrssafDeclarationRepository $declarations,
        private Transaction $transaction,
    ) {
    }

    public function __invoke(string $periodKey): void
    {
        $declaration = $this->declarations->find(DeclarationPeriod::fromKey($periodKey)->key());
        if (null !== $declaration) {
            $this->declarations->remove($declaration);
            $this->transaction->commit();
        }
    }
}
