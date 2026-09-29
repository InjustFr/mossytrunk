<?php

declare(strict_types=1);

namespace App\Application\Accounting\ExportOrders;

use App\Domain\Accounting\InvalidDeclaration;
use App\Domain\Order\Order;
use App\Domain\Order\OrderLine;
use App\Domain\Order\OrderRepository;
use App\Domain\Order\OrderSource;
use App\Domain\Order\PaymentMethod;
use App\Domain\Shared\DateRange;
use App\Domain\Shared\Money;

final readonly class ExportOrdersHandler
{
    private const array HEADER = ['Référence', 'Date', 'Heure', 'Source', 'Événement', 'Paiement', 'Articles', 'Détail', 'Sous-total', 'Remises', 'Frais de port', 'Total encaissé', "Coût d'achat", 'Marge'];

    public function __construct(
        private OrderRepository $orders,
    ) {
    }

    public function __invoke(string $from, string $to): OrdersCsv
    {
        $timezone = new \DateTimeZone(DateRange::TIMEZONE);
        $start = new \DateTimeImmutable($from, $timezone);
        $end = new \DateTimeImmutable($to, $timezone);
        if ($end < $start) {
            throw InvalidDeclaration::invalidRange();
        }
        $range = DateRange::fromDates($start, $end);

        $orders = array_values(array_filter($this->orders->list(), static fn (Order $order): bool => $range->covers($order->placedAt())));
        usort($orders, static fn (Order $a, Order $b): int => $a->placedAt() <=> $b->placedAt());

        $rows = [self::HEADER];
        foreach ($orders as $order) {
            $placedAt = $order->placedAt()->setTimezone($timezone);
            $rows[] = [
                $order->reference(),
                $placedAt->format('d/m/Y'),
                $placedAt->format('H:i'),
                self::source($order->source()),
                $order->event()?->name() ?? '',
                self::payment($order->paymentMethod()),
                (string) $order->itemCount(),
                implode(', ', array_map(static fn (OrderLine $line): string => \sprintf('%d × %s', $line->quantity(), $line->label()), $order->lines())),
                self::amount($order->subtotal()),
                self::amount($order->discountTotal()),
                self::amount($order->shipping()),
                self::amount($order->total()),
                self::amount($order->costOfGoods()),
                self::amount($order->total()->subtract($order->costOfGoods())),
            ];
        }

        return new OrdersCsv(
            \sprintf('commandes-%s-au-%s.csv', $start->format('Y-m-d'), $end->format('Y-m-d')),
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

    private static function source(OrderSource $source): string
    {
        return match ($source) {
            OrderSource::Manual => 'Saisie',
            OrderSource::SumUp => 'SumUp',
            OrderSource::Etsy => 'Etsy',
        };
    }

    private static function payment(?PaymentMethod $method): string
    {
        return match ($method) {
            PaymentMethod::Card => 'Carte',
            PaymentMethod::Cash => 'Espèces',
            null => '',
        };
    }
}
