<?php

declare(strict_types=1);

namespace App\Application\Order\GetOrder;

use App\Domain\Discount\AppliedDiscount;
use App\Domain\Order\ImportedSale;
use App\Domain\Order\Order;
use App\Domain\Order\OrderLine;
use App\Domain\Shared\DateRange;

final readonly class OrderView
{
    /**
     * @param list<array{id: string, productId: ?string, label: string, quantity: int, unitPrice: int, total: int, unitCost: int, cost: int}> $lines
     * @param array{id: string, name: string}|null                                                                                            $event
     * @param list<array{reference: string, paymentMethod: ?string}>                                                                          $importedSales
     * @param list<array{label: string, amount: int, ruleId: ?string}>                                                                        $discounts
     */
    public function __construct(
        public string $id,
        public string $reference,
        public string $placedAt,
        public ?array $event,
        public string $source,
        public string $sourceLabel,
        public ?string $paymentMethod,
        public array $lines,
        public array $discounts,
        public int $subtotal,
        public int $discountTotal,
        public int $shipping,
        public int $total,
        public int $costOfGoods,
        public int $margin,
        public array $importedSales,
        public ?string $refundedAt,
        public ?string $channelName,
    ) {
    }

    public static function fromOrder(Order $order, string $sourceLabel): self
    {
        return new self(
            (string) $order->id(),
            $order->reference(),
            $order->placedAt()->setTimezone(new \DateTimeZone(DateRange::TIMEZONE))->format(\DATE_ATOM),
            null === $order->event() ? null : ['id' => (string) $order->event()->id(), 'name' => $order->event()->name()],
            $order->source(),
            $sourceLabel,
            $order->paymentMethod()?->value,
            array_map(static fn (OrderLine $line): array => [
                'id' => (string) $line->id(),
                'productId' => null === $line->productId() ? null : (string) $line->productId(),
                'label' => $line->label(),
                'quantity' => $line->quantity(),
                'unitPrice' => $line->unitPrice()->amount(),
                'total' => $line->total()->amount(),
                'unitCost' => $line->unitCost()->amount(),
                'cost' => $line->cost()->amount(),
            ], $order->lines()),
            array_map(static fn (AppliedDiscount $discount): array => $discount->toArray(), $order->appliedDiscounts()),
            $order->subtotal()->amount(),
            $order->discountTotal()->amount(),
            $order->shipping()->amount(),
            $order->total()->amount(),
            $order->costOfGoods()->amount(),
            $order->total()->subtract($order->costOfGoods())->amount(),
            array_map(static fn (ImportedSale $sale): array => ['reference' => $sale->reference(), 'paymentMethod' => $sale->paymentMethod()?->value], $order->importedSales()),
            null === $order->refundedAt() ? null : $order->refundedAt()->setTimezone(new \DateTimeZone(DateRange::TIMEZONE))->format(\DATE_ATOM),
            $order->channel()?->name(),
        );
    }
}
