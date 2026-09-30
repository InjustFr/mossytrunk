<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Reporting;

use App\Domain\Event\Event;
use App\Domain\Order\Order;
use App\Domain\Order\OrderedItem;
use App\Domain\Product\Product;
use App\Domain\Reporting\ProductSales;
use App\Domain\Reporting\SalesByProduct;
use App\Domain\Shared\DateRange;
use App\Domain\Shared\Money;
use App\Tests\Support\Costs;
use App\Tests\Support\TestProductType;
use App\Tests\Support\TestWorkspace;
use PHPUnit\Framework\TestCase;

final class SalesByProductTest extends TestCase
{
    public function testSumsEveryVariantOfAProductAndRanksBySales(): void
    {
        $print = Costs::bought(Product::create(TestWorkspace::get(), 'PRI', 'Print', Money::cents(1_500), TestProductType::get(), ['A4', 'A3']), 300);
        $sticker = Product::create(TestWorkspace::get(), 'STI', 'Sticker', Money::cents(400), TestProductType::get());
        $event = Event::schedule(TestWorkspace::get(), 'Salon', 'Lyon', DateRange::fromDates(new \DateTimeImmutable('2026-05-09'), new \DateTimeImmutable('2026-05-10')));
        $at = new \DateTimeImmutable('2026-05-09 12:00', new \DateTimeZone('Europe/Paris'));

        $sales = SalesByProduct::of([
            Order::place($event, $at, [new OrderedItem($print->sellable('A4'), 1), new OrderedItem($sticker->sellable(null), 2)], []),
            Order::place($event, $at, [new OrderedItem($print->sellable('A3'), 2)], []),
        ]);

        $ranked = $sales->ranked();
        self::assertSame(['Print', 'Sticker'], array_map(static fn (ProductSales $product): string => $product->productName, $ranked));
        self::assertSame(3, $ranked[0]->quantity);
        self::assertSame(4_500, $ranked[0]->sales->amount());
        self::assertSame(900, $ranked[0]->cost->amount());
        self::assertTrue($sales->forProduct($sticker->id())?->unknownCost);
        self::assertNull(SalesByProduct::of([])->forProduct($print->id()));
    }
}
