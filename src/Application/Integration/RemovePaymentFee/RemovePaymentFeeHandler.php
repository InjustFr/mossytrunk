<?php

declare(strict_types=1);

namespace App\Application\Integration\RemovePaymentFee;

use App\Application\Transaction;
use App\Domain\Integration\ServiceConnectionRepository;
use Symfony\Component\Uid\Ulid;

final readonly class RemovePaymentFeeHandler
{
    public function __construct(
        private ServiceConnectionRepository $connections,
        private Transaction $transaction,
    ) {
    }

    public function __invoke(string $service, string $feeId): void
    {
        $this->connections->get($service)->removeFee(Ulid::fromString($feeId));
        $this->transaction->commit();
    }
}
