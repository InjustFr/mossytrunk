<?php

declare(strict_types=1);

namespace App\Tests\Functional\Application\SumUp;

use App\Application\Event\ScheduleEvent\ScheduleEvent;
use App\Application\Event\ScheduleEvent\ScheduleEventHandler;
use App\Application\Order\ListOrders\ListOrdersHandler;
use App\Application\Product\CreateProduct\CreateProduct;
use App\Application\Product\CreateProduct\CreateProductHandler;
use App\Application\Product\CreateProductType\CreateProductTypeHandler;
use App\Application\Product\ListProducts\ListProductsHandler;
use App\Application\SumUp\ImportFromSumUp\ImportFromSumUpHandler;
use App\Application\SumUp\SumUpLine;
use App\Application\SumUp\SumUpTransaction;
use App\Domain\Shared\Money;
use App\Infrastructure\SumUp\FakeSumUpGateway;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ImportFromSumUpTest extends KernelTestCase
{
    public function testImportsProductsAndOrdersLinkedToEvents(): void
    {
        $this->scheduleEvent('Salon de printemps', '2030-03-14', '2030-03-15');

        $report = $this->import();

        self::assertSame(3, $report->productsCreated, 'Sticker Mousse, Tote bag, Print A4 (Fougère)');
        self::assertSame(2, $report->ordersImported);
        self::assertSame(0, $report->ordersAlreadyImported);

        $orders = self::getContainer()->get(ListOrdersHandler::class)();
        self::assertSame(['TFAKE0002', 'TFAKE0001'], array_column($orders, 'reference'));
        self::assertSame(['sumup', 'sumup'], array_column($orders, 'source'));
        self::assertSame(1_000, $orders[1]->total, 'amount charged by SumUp');
        self::assertSame(200, $orders[1]->discountTotal, 'SumUp discount kept');

        $products = self::getContainer()->get(ListProductsHandler::class)();
        $sticker = $products[array_search('Sticker Mousse', array_column($products, 'name'), true)];
        self::assertSame(400, $sticker->sellingPrice);
        self::assertSame(0, $sticker->buyingPrice);
        self::assertSame('PRD-STICKER-MOUSSE', $sticker->reference);
    }

    public function testOrdersWithoutEventAreNotImportedAndReportedOnce(): void
    {
        $this->scheduleEvent('Salon de printemps', '2030-03-14', '2030-03-15');

        $report = $this->import();

        self::assertSame(2, $report->ordersWithoutEvent);
        // 2030-03-22T23:30Z is already the 23rd in Paris.
        self::assertSame(['2030-03-21', '2030-03-23'], $report->datesWithoutEvent);
        self::assertCount(2, self::getContainer()->get(ListOrdersHandler::class)());
    }

    public function testReimportDoesNotDuplicateAndPicksUpNewlyCoveredOrders(): void
    {
        $this->scheduleEvent('Salon de printemps', '2030-03-14', '2030-03-15');
        $this->import();

        $this->scheduleEvent('Marché', '2030-03-21', '2030-03-21');
        $report = $this->import();

        self::assertSame(0, $report->productsCreated);
        self::assertSame(1, $report->ordersImported);
        self::assertSame(2, $report->ordersAlreadyImported);
        self::assertSame(['2030-03-23'], $report->datesWithoutEvent);
        self::assertCount(3, self::getContainer()->get(ListOrdersHandler::class)());
    }

    public function testLinesAreMatchedToExistingProductVariants(): void
    {
        $this->scheduleEvent('Salon de printemps', '2030-03-14', '2030-03-15');
        self::getContainer()->get(CreateProductHandler::class)(new CreateProduct('Print A4', 2_000, 600, ['Mousse', 'Fougère']));
        self::getContainer()->get(CreateProductHandler::class)(new CreateProduct('Tote bag', 1_500, 500));

        $report = $this->import();

        self::assertSame(1, $report->productsCreated, 'only Sticker Mousse is new');
        $orders = self::getContainer()->get(ListOrdersHandler::class)();
        self::assertSame(3, $orders[0]->itemCount);
    }

    public function testProductWithVariantsButNoVariantInSumUpBlocksTheOrder(): void
    {
        $this->scheduleEvent('Salon de printemps', '2030-03-14', '2030-03-15');
        self::getContainer()->get(CreateProductHandler::class)(new CreateProduct('T-shirt', 2_000, 0, ['S', 'M']));
        self::getContainer()->get(FakeSumUpGateway::class)->willReturn([
            new SumUpTransaction('TX-TS', new \DateTimeImmutable('2030-03-14T12:00:00Z'), Money::cents(2_000), [new SumUpLine('T-shirt', Money::cents(2_000), 1)]),
        ]);

        $report = $this->import();

        self::assertSame(0, $report->ordersImported);
        self::assertSame(1, $report->ordersWithUnresolvedProducts);
        self::assertSame(['T-shirt'], $report->unresolvedProducts);
    }

    public function testCategoryBecomesTheProductType(): void
    {
        $this->scheduleEvent('Salon de printemps', '2030-03-14', '2030-03-15');
        self::getContainer()->get(FakeSumUpGateway::class)->willReturn([
            new SumUpTransaction('TX-CAT', new \DateTimeImmutable('2030-03-14T12:00:00Z'), Money::cents(3_000), [
                new SumUpLine('Print Forêt', Money::cents(1_500), 1, 'Print'),
                new SumUpLine('Rivière', Money::cents(1_500), 1, 'print'),
            ]),
        ]);

        $report = $this->import();

        self::assertSame(1, $report->typesCreated);
        $products = self::getContainer()->get(ListProductsHandler::class)();
        self::assertSame(['Print Forêt', 'Print Rivière'], array_column($products, 'displayName'));
        self::assertSame(['Forêt', 'Rivière'], array_column($products, 'name'));
        self::assertSame(['PRI-FORET', 'PRI-RIVIERE'], array_column($products, 'reference'));
    }

    public function testTypedProductsAreMatchedByDisplayName(): void
    {
        $this->scheduleEvent('Salon de printemps', '2030-03-14', '2030-03-15');
        $sticker = (string) self::getContainer()->get(CreateProductTypeHandler::class)('Sticker')->id();
        self::getContainer()->get(CreateProductHandler::class)(new CreateProduct('Mousse', 400, 60, typeId: $sticker));

        $report = $this->import();

        self::assertSame(2, $report->productsCreated, '"Sticker Mousse" is the existing typed product');
    }

    private function import(): \App\Application\SumUp\ImportFromSumUp\SumUpImportReport
    {
        $report = self::getContainer()->get(ImportFromSumUpHandler::class)();
        self::getContainer()->get('doctrine')->getManager()->clear();

        return $report;
    }

    private function scheduleEvent(string $name, string $start, string $end): void
    {
        self::getContainer()->get(ScheduleEventHandler::class)(new ScheduleEvent($name, 'Lyon', new \DateTimeImmutable($start), new \DateTimeImmutable($end)));
    }
}
