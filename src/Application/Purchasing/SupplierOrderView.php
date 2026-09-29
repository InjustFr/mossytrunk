<?php

declare(strict_types=1);

namespace App\Application\Purchasing;

use App\Domain\Purchasing\SupplierOrder;
use App\Domain\Purchasing\SupplierOrderLine;

final readonly class SupplierOrderView
{
    /**
     * @param array{id: string, name: string}                                                                                                                            $supplier
     * @param list<array{id: string, productId: string, variant: ?string, label: string, orderedQuantity: int, receivedQuantity: ?int, totalPrice: int, plannedUnitCost: int, unitCost: ?int}> $lines
     */
    public function __construct(
        public string $id,
        public string $reference,
        public array $supplier,
        public string $orderedOn,
        public string $status,
        public ?string $receivedAt,
        public int $total,
        public int $orderedUnits,
        public ?int $receivedUnits,
        public array $lines,
    ) {
    }

    public static function of(SupplierOrder $order): self
    {
        return new self(
            (string) $order->id(),
            $order->reference(),
            ['id' => (string) $order->supplier()->id(), 'name' => $order->supplier()->name()],
            $order->orderedOn()->format('Y-m-d'),
            $order->status()->value,
            $order->receivedAt()?->format(\DateTimeInterface::ATOM),
            $order->total()->amount(),
            $order->orderedUnits(),
            $order->receivedUnits(),
            array_map(static fn (SupplierOrderLine $line): array => [
                'id' => (string) $line->id(),
                'productId' => (string) $line->productId(),
                'variant' => $line->variant(),
                'label' => $line->label(),
                'orderedQuantity' => $line->orderedQuantity(),
                'receivedQuantity' => $line->receivedQuantity(),
                'totalPrice' => $line->totalPrice()->amount(),
                'plannedUnitCost' => $line->plannedUnitCost()->amount(),
                'unitCost' => $line->unitCost()?->amount(),
            ], $order->lines()),
        );
    }
}
