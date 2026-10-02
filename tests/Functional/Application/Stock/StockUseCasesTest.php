<?php

declare(strict_types=1);

namespace App\Tests\Functional\Application\Stock;

use App\Application\Event\GetEventReport\GetEventReportHandler;
use App\Application\Event\ListEvents\EventSummaryView;
use App\Application\Event\ListEvents\ListEventsHandler;
use App\Application\Event\ScheduleEvent\ScheduleEvent;
use App\Application\Event\ScheduleEvent\ScheduleEventHandler;
use App\Application\Order\DeleteOrder\DeleteOrderHandler;
use App\Application\Order\GetOrder\GetOrderHandler;
use App\Application\Order\PlaceOrder\PlaceOrder;
use App\Application\Order\PlaceOrder\PlaceOrderHandler;
use App\Application\Order\RefundOrder\RefundOrderHandler;
use App\Application\Order\RequestedLine;
use App\Application\Product\CreateProduct\CreateProduct;
use App\Application\Product\CreateProduct\CreateProductHandler;
use App\Application\Product\CreateProductType\CreateProductTypeHandler;
use App\Application\Product\GetProduct\GetProductHandler;
use App\Application\Product\ListProducts\ListProductsHandler;
use App\Application\Product\ListProducts\ProductView;
use App\Application\Product\MoveVariant\MoveVariant;
use App\Application\Product\MoveVariant\MoveVariantHandler;
use App\Application\Product\UpdateProduct\UpdateProduct;
use App\Application\Product\UpdateProduct\UpdateProductHandler;
use App\Application\Stock\DismissDiscrepancy\DismissDiscrepancyHandler;
use App\Application\Stock\GetProductStock\GetProductStockHandler;
use App\Application\Stock\GetStockSheet\GetStockSheetHandler;
use App\Application\Stock\ListStockChecks\ListStockChecksHandler;
use App\Application\Stock\Restock\Restock;
use App\Application\Stock\Restock\RestockHandler;
use App\Application\Stock\TakeStockCheck\CountedItem;
use App\Application\Stock\TakeStockCheck\TakeStockCheck;
use App\Application\Stock\TakeStockCheck\TakeStockCheckHandler;
use App\Domain\Order\Order;
use App\Domain\Product\ProductKind;
use App\Domain\Shared\Exception\NotFound;
use App\Tests\Support\ActsAsUser;
use App\Tests\Support\CreatesProducts;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class StockUseCasesTest extends KernelTestCase
{
    use ActsAsUser;
    use CreatesProducts;
    private string $eventId;
    private string $sticker;
    private string $tshirt;

    protected function setUp(): void
    {
        self::actAsMemberOf();
        $this->eventId = (string) self::getContainer()->get(ScheduleEventHandler::class)(
            new ScheduleEvent('Japan Expo', 'Villepinte', new \DateTimeImmutable('2026-07-09'), new \DateTimeImmutable('2026-07-12')),
        );
        $this->sticker = (string) self::createProduct('Sticker', 400, 80, lowStockThreshold: 5);
        $this->tshirt = (string) self::createProduct('T-shirt', 2_000, 900, ['S', 'M']);
    }

    public function testOrdersSellTheOldestStockFirstAndCostWhatItWasPaid(): void
    {
        $this->restock($this->sticker, null, 10, 500);
        $this->restock($this->sticker, null, 10, 1_000);

        $order = $this->place('2026-07-10 15:00', [new RequestedLine($this->sticker, null, 12)]);
        $this->clear();

        self::assertSame(700, self::getContainer()->get(GetOrderHandler::class)((string) $order->id())->costOfGoods);
        $product = $this->product($this->sticker);
        self::assertSame(8, $product->onHand);
        self::assertSame(100, $product->stockUnitCost);
        self::assertSame(100, $product->buyingPrice, 'the last purchase price becomes the buying price');
        self::assertFalse($product->lowStock);
    }

    public function testSellingMoreThanTheStockGoesNegativeAtTheLastPurchasePrice(): void
    {
        $this->restock($this->tshirt, 'M', 1, 1_000);

        $order = $this->place('2026-07-10 15:00', [new RequestedLine($this->tshirt, 'M', 3)]);
        $this->clear();

        self::assertSame(3_000, self::getContainer()->get(GetOrderHandler::class)((string) $order->id())->costOfGoods);
        $product = $this->product($this->tshirt);
        self::assertSame(-2, $product->onHand);
        self::assertTrue($product->negativeStock);
        self::assertSame([
            ['variant' => 'S', 'onHand' => 0, 'low' => true, 'negative' => false],
            ['variant' => 'M', 'onHand' => -2, 'low' => true, 'negative' => true],
        ], $product->stock);
    }

    public function testDeletingAnOrderErasesItsWithdrawalAsIfItNeverHappened(): void
    {
        $this->restock($this->sticker, null, 10, 1_000);
        $order = $this->place('2026-07-10 15:00', [new RequestedLine($this->sticker, null, 4)]);

        self::getContainer()->get(DeleteOrderHandler::class)((string) $order->id());
        $this->clear();

        self::assertSame(10, $this->product($this->sticker)->onHand);
        $lots = self::getContainer()->get(GetProductStockHandler::class)($this->sticker)[0]->lots;
        self::assertSame([['purchase', 10, 10]], array_map(static fn (array $lot): array => [$lot['origin'], $lot['quantity'], $lot['remaining']], $lots));
        self::assertSame(['purchase'], array_column(self::getContainer()->get(GetProductHandler::class)($this->sticker)->movements, 'kind'));
    }

    public function testRefundingAnOrderKeepsTheSaleAndTakesItsUnitsBackAsAReturn(): void
    {
        $this->restock($this->sticker, null, 10, 1_000);
        $order = $this->place('2026-07-10 15:00', [new RequestedLine($this->sticker, null, 4)]);

        self::getContainer()->get(RefundOrderHandler::class)((string) $order->id());
        $this->clear();

        $product = $this->product($this->sticker);
        self::assertSame(10, $product->onHand);
        self::assertSame(0, $product->unitsSold, 'a refunded order no longer counts as a sale');
        self::assertSame([0, 0], [$this->event()->orderCount, $this->event()->turnover]);
        self::assertNotNull(self::getContainer()->get(GetOrderHandler::class)((string) $order->id())->refundedAt);
        $movements = self::getContainer()->get(GetProductHandler::class)($this->sticker)->movements;
        self::assertEqualsCanonicalizing([['return', 4, '/orders/'.$order->id()], ['sale', -4, '/orders/'.$order->id()], ['purchase', 10, null]], array_map(static fn (array $movement): array => [$movement['kind'], $movement['quantity'], $movement['link']], $movements));
    }

    public function testDeletingARefundedOrderErasesBothItsSaleAndItsReturn(): void
    {
        $this->restock($this->sticker, null, 10, 1_000);
        $order = $this->place('2026-07-10 15:00', [new RequestedLine($this->sticker, null, 4)]);
        self::getContainer()->get(RefundOrderHandler::class)((string) $order->id());

        self::getContainer()->get(DeleteOrderHandler::class)((string) $order->id());
        $this->clear();

        self::assertSame(10, $this->product($this->sticker)->onHand);
        $lots = self::getContainer()->get(GetProductStockHandler::class)($this->sticker)[0]->lots;
        self::assertSame([['purchase', 10, 10]], array_map(static fn (array $lot): array => [$lot['origin'], $lot['quantity'], $lot['remaining']], $lots));
    }

    public function testAStockCheckFlagsMissingUnitsUntilTheMissingOrderIsAdded(): void
    {
        $this->restock($this->sticker, null, 10, 1_000);
        $this->place('2026-07-10 15:00', [new RequestedLine($this->sticker, null, 2)]);

        $checkId = (string) self::getContainer()->get(TakeStockCheckHandler::class)(new TakeStockCheck($this->eventId, [new CountedItem($this->sticker, null, 5)]));
        $this->clear();

        $check = $this->checks()[0];
        self::assertSame(3, $check->unexplainedUnits);
        self::assertSame(1_200, $check->missedSales);
        self::assertSame(5, $this->product($this->sticker)->onHand);
        self::assertSame(3, $this->event()->unexplainedUnits);

        $late = $this->place('2026-07-11 18:00', [new RequestedLine($this->sticker, null, 2)]);
        $this->clear();

        self::assertSame(5, $this->product($this->sticker)->onHand, 'the check already removed those units');
        self::assertSame(200, self::getContainer()->get(GetOrderHandler::class)((string) $late->id())->costOfGoods);
        self::assertSame(1, $this->checks()[0]->unexplainedUnits);

        self::getContainer()->get(DismissDiscrepancyHandler::class)($checkId, $this->checks()[0]->lines[0]['id']);
        $this->clear();

        self::assertSame(0, $this->event()->unexplainedUnits);
        self::assertTrue($this->checks()[0]->lines[0]['dismissed']);
    }

    public function testSuppliesMissingAtTheInventoryAreConsumedByTheEvent(): void
    {
        $flyer = (string) self::getContainer()->get(CreateProductHandler::class)(new CreateProduct('Flyer', 0, typeId: (string) self::getContainer()->get(CreateProductTypeHandler::class)('Papeterie')->id(), kind: ProductKind::Supply));
        $this->restock($flyer, null, 100, 1_000);
        $this->restock($this->sticker, null, 10, 1_000);
        $this->place('2026-07-10 15:00', [new RequestedLine($this->sticker, null, 2)]);

        self::getContainer()->get(TakeStockCheckHandler::class)(new TakeStockCheck($this->eventId, [new CountedItem($flyer, null, 70), new CountedItem($this->sticker, null, 8)]));
        $this->clear();

        $line = array_values(array_filter($this->checks()[0]->lines, static fn (array $line): bool => $line['supply']))[0];
        self::assertSame([0, 30, 300], [$line['unexplained'], $line['consumed'], $line['consumedCost']]);
        self::assertSame(0, $this->event()->unexplainedUnits);
        $report = self::getContainer()->get(GetEventReportHandler::class)($this->eventId);
        self::assertSame(300, $report->total['consumedSupplies']);
        self::assertSame($report->total['turnover'] - $report->total['costOfGoods'] - 300 - $report->total['urssaf'], $report->total['result']);
        self::assertSame($report->total['result'], $this->event()->result);
    }

    public function testStockSheetListsEverySellableItemWithWhatTheEventSold(): void
    {
        $this->restock($this->tshirt, 'S', 4, 3_600);
        $this->place('2026-07-10 15:00', [new RequestedLine($this->tshirt, 'S', 1)]);

        $sheet = self::getContainer()->get(GetStockSheetHandler::class)($this->eventId);

        $small = array_values(array_filter($sheet, static fn ($line): bool => 'S' === $line->variant))[0];
        self::assertSame(3, $small->onHand);
        self::assertSame(1, $small->soldAtEvent);
        self::assertCount(3, $sheet);
    }

    public function testMovingAVariantMovesItsStock(): void
    {
        $hoodie = (string) self::createProduct('Hoodie', 4_000, 1_500, ['M']);
        $this->restock($this->tshirt, 'S', 4, 3_600);
        $this->restock($hoodie, 'M', 1, 1_500);

        self::getContainer()->get(MoveVariantHandler::class)(new MoveVariant($this->tshirt, 'S', $hoodie, null, 'M'));
        $this->clear();

        self::assertSame([['variant' => 'M', 'onHand' => 5, 'low' => true, 'negative' => false]], $this->product($hoodie)->stock);
        self::assertSame(0, $this->product($this->tshirt)->onHand);
    }

    public function testRemovingAVariantForgetsItsStock(): void
    {
        $this->restock($this->tshirt, 'S', 4, 3_600);

        self::getContainer()->get(UpdateProductHandler::class)(new UpdateProduct($this->tshirt, 'T-shirt', 2_000, ['M']));
        self::getContainer()->get(UpdateProductHandler::class)(new UpdateProduct($this->tshirt, 'T-shirt', 2_000, ['S', 'M']));
        $this->clear();

        self::assertSame(0, $this->product($this->tshirt)->onHand);
    }

    public function testAUniqueProductGivenVariantsKeepsItsStockOnTheFirstOne(): void
    {
        $this->restock($this->sticker, null, 10, 1_000);

        self::getContainer()->get(UpdateProductHandler::class)(new UpdateProduct($this->sticker, 'Sticker', 400, ['M', 'S'], lowStockThreshold: 5));
        $this->clear();

        self::assertSame([
            ['variant' => 'M', 'onHand' => 10, 'low' => false, 'negative' => false],
            ['variant' => 'S', 'onHand' => 0, 'low' => true, 'negative' => false],
        ], $this->product($this->sticker)->stock);
        self::assertSame(100, $this->product($this->sticker)->stockUnitCost);
    }

    public function testAProductLosingAllItsVariantsKeepsTheirStock(): void
    {
        $tote = (string) self::getContainer()->get(CreateProductTypeHandler::class)('Tote')->id();
        $this->restock($this->tshirt, 'S', 4, 3_600);
        $this->restock($this->tshirt, 'M', 2, 1_800);

        self::getContainer()->get(UpdateProductHandler::class)(new UpdateProduct($this->tshirt, 'T-shirt', 2_000, [], $tote));
        $this->clear();

        self::assertSame([['variant' => null, 'onHand' => 6, 'low' => true, 'negative' => false]], $this->product($this->tshirt)->stock);
    }

    public function testStockBelongsToTheWorkspace(): void
    {
        $this->restock($this->sticker, null, 10, 1_000);

        self::actAsMemberOf('Autre atelier');

        $this->expectException(NotFound::class);
        self::getContainer()->get(GetProductStockHandler::class)($this->sticker);
    }

    private function restock(string $productId, ?string $variant, int $quantity, int $totalPaid): void
    {
        self::getContainer()->get(RestockHandler::class)(new Restock($productId, $variant, $quantity, $totalPaid));
    }

    /**
     * @param list<RequestedLine> $lines
     */
    private function place(string $placedAt, array $lines): Order
    {
        return self::getContainer()->get(PlaceOrderHandler::class)(new PlaceOrder(new \DateTimeImmutable($placedAt, new \DateTimeZone('Europe/Paris')), $lines));
    }

    private function product(string $id): ProductView
    {
        return array_values(array_filter(self::getContainer()->get(ListProductsHandler::class)(), static fn (ProductView $view): bool => $view->id === $id))[0];
    }

    private function event(): EventSummaryView
    {
        return array_values(array_filter(self::getContainer()->get(ListEventsHandler::class)(), fn (EventSummaryView $view): bool => $view->id === $this->eventId))[0];
    }

    /**
     * @return list<\App\Application\Stock\ListStockChecks\StockCheckView>
     */
    private function checks(): array
    {
        return self::getContainer()->get(ListStockChecksHandler::class)($this->eventId);
    }

    private function clear(): void
    {
        self::getContainer()->get('doctrine')->getManager()->clear();
    }
}
