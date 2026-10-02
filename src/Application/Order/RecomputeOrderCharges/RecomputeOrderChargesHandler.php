<?php

declare(strict_types=1);

namespace App\Application\Order\RecomputeOrderCharges;

use App\Application\Transaction;
use App\Domain\Order\OrderRepository;
use Symfony\Component\Uid\Ulid;

final readonly class RecomputeOrderChargesHandler
{
    public function __construct(
        private OrderRepository $orders,
        private Transaction $transaction,
    ) {
    }

    /**
     * @param list<string> $orderIds
     */
    public function __invoke(array $orderIds): int
    {
        $ids = array_values(array_unique($orderIds));
        foreach ($ids as $id) {
            $this->orders->get(Ulid::fromString($id))->chargeChannelCosts();
        }
        $this->transaction->commit();

        return \count($ids);
    }
}
