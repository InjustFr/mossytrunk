<?php

declare(strict_types=1);

namespace App\Application\Order;

use App\Domain\Integration\ServiceConnectionRepository;
use App\Domain\Order\Order;

final readonly class OrderCharges
{
    public function __construct(private ServiceConnectionRepository $connections)
    {
    }

    public function charge(Order $order): void
    {
        $service = $order->channel()?->service();
        $connection = null === $service ? null : $this->connections->find($service);
        $order->chargeChannelCosts($connection?->feesOn($order->paymentMethod(), $order->total()) ?? []);
    }
}
