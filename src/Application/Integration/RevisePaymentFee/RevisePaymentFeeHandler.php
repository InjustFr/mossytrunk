<?php

declare(strict_types=1);

namespace App\Application\Integration\RevisePaymentFee;

use App\Application\Transaction;
use App\Domain\Integration\ServiceConnectionRepository;
use App\Domain\Order\PaymentMethod;
use App\Domain\Sales\ChannelCostKind;
use Symfony\Component\Uid\Ulid;

final readonly class RevisePaymentFeeHandler
{
    public function __construct(
        private ServiceConnectionRepository $connections,
        private Transaction $transaction,
    ) {
    }

    public function __invoke(string $service, string $feeId, PaymentMethod $paymentMethod, string $label, ChannelCostKind $kind, int $amount): void
    {
        $this->connections->get($service)->reviseFee(Ulid::fromString($feeId), $paymentMethod, $label, $kind, $amount);
        $this->transaction->commit();
    }
}
