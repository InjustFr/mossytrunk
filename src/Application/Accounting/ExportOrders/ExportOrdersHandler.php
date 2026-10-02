<?php

declare(strict_types=1);

namespace App\Application\Accounting\ExportOrders;

use App\Application\Integration\Connectors;
use App\Application\Translator;
use App\Domain\Accounting\Exception\DeclarationPeriodEndsBeforeStart;
use App\Domain\Order\ImportedSale;
use App\Domain\Order\OrderLine;
use App\Domain\Order\OrderRepository;
use App\Domain\Order\PaymentMethod;
use App\Domain\Shared\DateRange;
use App\Domain\Shared\Money;

final readonly class ExportOrdersHandler
{
    private const array COLUMNS = ['reference', 'externalReferences', 'date', 'time', 'source', 'event', 'payment', 'items', 'detail', 'subtotal', 'discounts', 'shipping', 'total', 'cost', 'supplies', 'channelCosts', 'margin'];

    public function __construct(
        private OrderRepository $orders,
        private Connectors $connectors,
        private Translator $translator,
    ) {
    }

    public function __invoke(string $from, string $to): OrdersCsv
    {
        $timezone = new \DateTimeZone(DateRange::TIMEZONE);
        $start = new \DateTimeImmutable($from, $timezone);
        $end = new \DateTimeImmutable($to, $timezone);
        if ($end < $start) {
            throw new DeclarationPeriodEndsBeforeStart();
        }

        $orders = array_reverse($this->orders->salesWithin(DateRange::fromDates($start, $end)));

        $rows = [array_map(fn (string $column): string => $this->translator->trans('export.orders.column.'.$column), self::COLUMNS)];
        foreach ($orders as $order) {
            $placedAt = $order->placedAt()->setTimezone($timezone);
            $rows[] = [
                $order->reference(),
                implode(', ', array_map(static fn (ImportedSale $sale): string => $sale->reference(), $order->importedSales())),
                $placedAt->format('d/m/Y'),
                $placedAt->format('H:i'),
                $this->connectors->labelOf($order->source()),
                $order->event()?->name() ?? '',
                $this->payment($order->paymentMethod()),
                (string) $order->itemCount(),
                implode(', ', array_map(static fn (OrderLine $line): string => \sprintf('%d × %s', $line->quantity(), $line->label()), $order->lines())),
                self::amount($order->subtotal()),
                self::amount($order->discountTotal()),
                self::amount($order->shipping()),
                self::amount($order->total()),
                self::amount($order->costOfGoods()),
                self::amount($order->suppliesCost()),
                self::amount($order->channelCosts()),
                self::amount($order->total()->subtract($order->costOfGoods())->subtract($order->suppliesCost())->subtract($order->channelCosts())),
            ];
        }

        return new OrdersCsv(
            $this->translator->trans('export.orders.filename', ['from' => $start->format('Y-m-d'), 'to' => $end->format('Y-m-d')]),
            "\u{FEFF}".implode("\r\n", array_map(self::line(...), $rows))."\r\n",
            \count($orders),
        );
    }

    /**
     * @param list<string> $cells
     */
    private static function line(array $cells): string
    {
        return implode(';', array_map(static fn (string $cell): string => 1 === preg_match('/[;"\r\n]/', $cell) ? '"'.str_replace('"', '""', $cell).'"' : $cell, $cells));
    }

    private static function amount(Money $money): string
    {
        return number_format($money->amount() / 100, 2, ',', '');
    }

    private function payment(?PaymentMethod $method): string
    {
        return null === $method ? '' : $this->translator->trans('export.orders.payment.'.$method->value);
    }
}
