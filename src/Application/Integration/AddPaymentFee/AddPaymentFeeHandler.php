<?php

declare(strict_types=1);

namespace App\Application\Integration\AddPaymentFee;

use App\Application\Transaction;
use App\Domain\Integration\ServiceConnectionRepository;
use App\Domain\Order\PaymentMethod;
use App\Domain\Sales\ChannelCostKind;
use Symfony\Component\Uid\Ulid;

final readonly class AddPaymentFeeHandler
{
    public function __construct(
        private ServiceConnectionRepository $connections,
        private Transaction $transaction,
    ) {
    }

    public function __invoke(string $service, PaymentMethod $paymentMethod, string $label, ChannelCostKind $kind, int $amount): Ulid
    {
        $fee = $this->connections->get($service)->addFee($paymentMethod, $label, $kind, $amount);
        $this->transaction->commit();

        return $fee->id();
    }
}
