<?php

declare(strict_types=1);

namespace App\Application\Order;

use App\Application\Integration\Connectors;
use App\Application\Translator;
use App\Domain\Order\Order;
use App\Domain\Sales\OrderCharge;

final readonly class OrderCharges
{
    public function __construct(
        private Connectors $connectors,
        private Translator $translator,
    ) {
    }

    public function charge(Order $order): void
    {
        $order->chargeChannelCosts($this->reportedFees($order));
    }

    /**
     * @return list<OrderCharge>
     */
    private function reportedFees(Order $order): array
    {
        $fees = $order->saleFees();
        if (null === $fees || $fees->isZero()) {
            return [];
        }

        $service = $this->connectors->get($order->source())->describe()->label;

        return [new OrderCharge($this->translator->trans('import.fee', ['service' => $service]), $fees)];
    }
}
