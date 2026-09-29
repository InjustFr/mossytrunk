<?php

declare(strict_types=1);

namespace App\Application\Design\WithdrawDeclination;

use App\Application\Transaction;
use App\Domain\Design\DesignRepository;
use Symfony\Component\Uid\Ulid;

final readonly class WithdrawDeclinationHandler
{
    public function __construct(
        private DesignRepository $designs,
        private Transaction $transaction,
    ) {
    }

    public function __invoke(string $designId, string $declinationId): void
    {
        $this->designs->get(Ulid::fromString($designId))->withdraw(Ulid::fromString($declinationId));
        $this->transaction->commit();
    }
}
