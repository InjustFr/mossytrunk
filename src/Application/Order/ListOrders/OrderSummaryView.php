<?php

declare(strict_types=1);

namespace App\Application\Order\ListOrders;

use App\Domain\Order\ImportedSale;
use App\Domain\Order\Order;
use App\Domain\Order\OrderLine;
use App\Domain\Shared\DateRange;

final readonly class OrderSummaryView
{
    /**
     * @param list<string> $externalReferences
     */
    public function __construct(
        public string $id,
        public string $reference,
        public string $placedAt,
        public ?string $eventId,
        public ?string $eventName,
        public int $itemCount,
        public int $subtotal,
        public int $discountTotal,
        public int $total,
        public string $source,
        public string $sourceLabel,
        public ?string $paymentMethod,
        public array $externalReferences,
        public ?string $refundedAt,
        public int $unidentifiedLines,
        public ?string $channelId,
        public ?string $channelName,
        public int $profit,
        public int $unknownCosts,
    ) {
    }

    public static function fromOrder(Order $order, string $sourceLabel): self
    {
        return new self(
            (string) $order->id(),
            $order->reference(),
            $order->placedAt()->setTimezone(new \DateTimeZone(DateRange::TIMEZONE))->format(\DATE_ATOM),
            null === $order->event() ? null : (string) $order->event()->id(),
            $order->event()?->name(),
            $order->itemCount(),
            $order->subtotal()->amount(),
            $order->discountTotal()->amount(),
            $order->total()->amount(),
            $order->source(),
            $sourceLabel,
            $order->paymentMethod()?->value,
            array_map(static fn (ImportedSale $sale): string => $sale->reference(), $order->importedSales()),
            null === $order->refundedAt() ? null : $order->refundedAt()->setTimezone(new \DateTimeZone(DateRange::TIMEZONE))->format(\DATE_ATOM),
            \count(array_filter($order->lines(), static fn (OrderLine $line): bool => null === $line->productId())),
            null === $order->channel() ? null : (string) $order->channel()->id(),
            $order->channel()?->name(),
            $order->total()->subtract($order->costOfGoods())->subtract($order->suppliesCost())->subtract($order->channelCosts())->amount(),
            $order->unknownCostLines(),
        );
    }
}
