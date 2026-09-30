<?php

declare(strict_types=1);

namespace App\Tests\Functional\Application\Integration;

use App\Application\Discount\CreateDiscountRule\CreateDiscountRuleHandler;
use App\Application\Event\ScheduleEvent\ScheduleEvent;
use App\Application\Event\ScheduleEvent\ScheduleEventHandler;
use App\Application\Integration\ConfigureConnection\AddConnectionHandler;
use App\Application\Integration\Exception\ServiceNotAdded;
use App\Application\Integration\ImportSales\ImportReport;
use App\Application\Integration\ImportSales\ImportSalesHandler;
use App\Application\Order\GetOrder\GetOrderHandler;
use App\Application\Order\ListOrders\ListOrdersHandler;
use App\Application\Product\CreateProductType\CreateProductTypeHandler;
use App\Application\Product\ListProducts\ListProductsHandler;
use App\Application\Stock\Restock\Restock;
use App\Application\Stock\Restock\RestockHandler;
use App\Domain\Shared\Money;
use App\Infrastructure\Connector\SumUp\FakeSumUpGateway;
use App\Tests\Support\ActsAsUser;
use App\Tests\Support\CreatesProducts;
use App\Tests\Support\DiscountRules;
use App\Tests\Support\ExternalSales;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ImportSumUpSalesTest extends KernelTestCase
{
    use ActsAsUser;
    use CreatesProducts;

    protected function setUp(): void
    {
        self::actAsMemberOf();
        ExternalSales::connect(self::getContainer()->get(AddConnectionHandler::class), 'sumup', ['merchant_code' => 'MCODE', 'api_key' => 'sup_sk_test']);
    }

    public function testRequiresSumUpToBeAddedToTheWorkspace(): void
    {
        self::actAsMemberOf('Sans SumUp');

        $this->expectExceptionObject(new ServiceNotAdded('SumUp'));
        $this->import();
    }

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
        self::assertSame(['cash', 'card'], array_column($orders, 'paymentMethod'));
        self::assertSame(1_000, $orders[1]->total, 'amount charged by SumUp');
        self::assertSame(200, $orders[1]->discountTotal, 'SumUp discount kept');

        $products = self::getContainer()->get(ListProductsHandler::class)();
        $index = array_search('Sticker Mousse', array_column($products, 'name'), true);
        self::assertIsInt($index);
        $sticker = $products[$index];
        self::assertSame(400, $sticker->sellingPrice);
        self::assertSame(0, $sticker->buyingPrice);
        self::assertSame('PRD-STICKER-MOUSSE', $sticker->reference);
    }

    public function testOrdersWithoutEventAreNotImportedAndReportedOnce(): void
    {
        $this->scheduleEvent('Salon de printemps', '2030-03-14', '2030-03-15');

        $report = $this->import();

        self::assertSame(2, $report->ordersWithoutEvent);
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
        self::createProduct('Print A4', 2_000, 600, ['Mousse', 'Fougère']);
        self::createProduct('Tote bag', 1_500, 500);

        $report = $this->import();

        self::assertSame(1, $report->productsCreated, 'only Sticker Mousse is new');
        $orders = self::getContainer()->get(ListOrdersHandler::class)();
        self::assertSame(3, $orders[0]->itemCount);
    }

    public function testImportedOrdersTakeTheirUnitsFromStockInChronologicalOrder(): void
    {
        $this->scheduleEvent('Salon de printemps', '2030-03-14', '2030-03-15');
        $badge = (string) self::createProduct('Badge', 300, 50);
        self::getContainer()->get(RestockHandler::class)(new Restock($badge, null, 2, 100));
        self::getContainer()->get(RestockHandler::class)(new Restock($badge, null, 2, 400));
        self::getContainer()->get(FakeSumUpGateway::class)->willReturn([
            ExternalSales::sumUp('TX-LATE', new \DateTimeImmutable('2030-03-14T15:00:00Z'), Money::cents(600), [ExternalSales::line('Badge', Money::cents(300), 2)]),
            ExternalSales::sumUp('TX-EARLY', new \DateTimeImmutable('2030-03-14T10:00:00Z'), Money::cents(600), [ExternalSales::line('Badge', Money::cents(300), 2)]),
        ]);

        $this->import();
        self::getContainer()->get('doctrine')->getManager()->clear();

        $orders = self::getContainer()->get(ListOrdersHandler::class)();
        $costs = [];
        foreach ($orders as $order) {
            $costs[$order->reference] = self::getContainer()->get(GetOrderHandler::class)($order->id)->costOfGoods;
        }
        self::assertSame(['TX-LATE' => 400, 'TX-EARLY' => 100], $costs);
        $product = array_values(array_filter(self::getContainer()->get(ListProductsHandler::class)(), static fn ($view): bool => $view->id === $badge))[0];
        self::assertSame(0, $product->onHand);
    }

    public function testProductWithVariantsButNoVariantInSumUpWaitsToBeLinked(): void
    {
        $this->scheduleEvent('Salon de printemps', '2030-03-14', '2030-03-15');
        self::createProduct('T-shirt', 2_000, 0, ['S', 'M']);
        self::getContainer()->get(FakeSumUpGateway::class)->willReturn([
            ExternalSales::sumUp('TX-TS', new \DateTimeImmutable('2030-03-14T12:00:00Z'), Money::cents(2_000), [ExternalSales::line('T-shirt', Money::cents(2_000), 1)]),
        ]);

        $report = $this->import();

        self::assertSame(0, $report->ordersImported);
        self::assertSame(1, $report->ordersWaitingForItems);
        self::assertSame(1, $report->itemsToLink);
    }

    public function testADiscountRuleMatchingSumUpsDiscountReplacesIt(): void
    {
        $this->scheduleEvent('Salon de printemps', '2030-03-14', '2030-03-15');
        $print = (string) self::getContainer()->get(CreateProductTypeHandler::class)('Print')->id();
        $sticker = (string) self::getContainer()->get(CreateProductTypeHandler::class)('Sticker')->id();
        self::createProduct('Forêt', 1_200, typeId: $print);
        self::createProduct('Mousse', 600, typeId: $sticker);
        $rule = (string) self::getContainer()->get(CreateDiscountRuleHandler::class)(
            DiscountRules::fixedPrice('2 prints et 1 sticker pour 18 €', 1_800, DiscountRules::type($print, 2), DiscountRules::type($sticker, 1)),
        );
        $lines = [ExternalSales::line('Print Forêt', Money::cents(900), 2), ExternalSales::line('Sticker Mousse', Money::cents(450), 1)];
        self::getContainer()->get(FakeSumUpGateway::class)->willReturn([
            ExternalSales::sumUp('TX-RULE', new \DateTimeImmutable('2030-03-14T12:00:00Z'), Money::cents(1_800), $lines),
            ExternalSales::sumUp('TX-ROUNDED', new \DateTimeImmutable('2030-03-14T13:00:00Z'), Money::cents(1_801), $lines),
            ExternalSales::sumUp('TX-OTHER', new \DateTimeImmutable('2030-03-14T14:00:00Z'), Money::cents(1_750), $lines),
        ]);

        $this->import();

        $orders = array_column(self::getContainer()->get(ListOrdersHandler::class)(), 'id', 'reference');
        $discounts = static fn (string $reference): array => self::getContainer()->get(GetOrderHandler::class)($orders[$reference])->discounts;
        self::assertSame([['label' => '2 prints et 1 sticker pour 18 €', 'amount' => 1_200, 'ruleId' => $rule]], $discounts('TX-RULE'));
        self::assertSame([['label' => '2 prints et 1 sticker pour 18 €', 'amount' => 1_200, 'ruleId' => $rule]], $discounts('TX-ROUNDED'));
        self::assertSame(1_800, self::getContainer()->get(GetOrderHandler::class)($orders['TX-ROUNDED'])->total);
        self::assertSame([['label' => 'Remise SumUp', 'amount' => 1_250, 'ruleId' => null]], $discounts('TX-OTHER'));
    }

    public function testCategoryBecomesTheProductType(): void
    {
        $this->scheduleEvent('Salon de printemps', '2030-03-14', '2030-03-15');
        self::getContainer()->get(FakeSumUpGateway::class)->willReturn([
            ExternalSales::sumUp('TX-CAT', new \DateTimeImmutable('2030-03-14T12:00:00Z'), Money::cents(3_000), [
                ExternalSales::line('Print Forêt', Money::cents(1_500), 1, 'Print'),
                ExternalSales::line('Rivière', Money::cents(1_500), 1, 'print'),
            ]),
        ]);

        $report = $this->import();

        self::assertSame(1, $report->typesCreated);
        $products = self::getContainer()->get(ListProductsHandler::class)();
        self::assertSame(['Print Forêt', 'Print Rivière'], array_column($products, 'displayName'));
        self::assertSame(['Forêt', 'Rivière'], array_column($products, 'name'));
        self::assertSame(['PRI-FORET', 'PRI-RIVIERE'], array_column($products, 'reference'));
    }

    public function testLinesWithoutNameAreSoldAsAFreeAmountAtTheirOwnPrice(): void
    {
        $this->scheduleEvent('Salon de printemps', '2030-03-14', '2030-03-15');
        self::getContainer()->get(FakeSumUpGateway::class)->willReturn([
            ExternalSales::sumUp('TX-FREE1', new \DateTimeImmutable('2030-03-14T12:00:00Z'), Money::cents(700), [ExternalSales::line('', Money::cents(700), 1)]),
            ExternalSales::sumUp('TX-FREE2', new \DateTimeImmutable('2030-03-14T13:00:00Z'), Money::cents(2_500), [ExternalSales::line(' ', Money::cents(2_500), 1, 'Print')]),
        ]);

        $report = $this->import();

        self::assertSame(2, $report->ordersImported);
        self::assertSame(1, $report->productsCreated);
        self::assertSame(['Montant libre'], array_column(self::getContainer()->get(ListProductsHandler::class)(), 'name'));
        self::assertEqualsCanonicalizing([700, 2_500], array_column(self::getContainer()->get(ListOrdersHandler::class)(), 'total'));
    }

    public function testDiscountedLinesNeitherLowerTheProductPriceNorTheOrderTotal(): void
    {
        $this->scheduleEvent('Salon de printemps', '2030-03-14', '2030-03-15');
        self::createProduct('Mousse', 283);
        self::getContainer()->get(FakeSumUpGateway::class)->willReturn([
            ExternalSales::sumUp('TX-BUNDLE', new \DateTimeImmutable('2030-03-14T12:00:00Z'), Money::cents(800), [
                ExternalSales::line('Calcifer', Money::cents(267), 1),
                ExternalSales::line('Lichen', Money::cents(267), 1),
                ExternalSales::line('Fougère', Money::cents(266), 1),
            ]),
            ExternalSales::sumUp('TX-SINGLE', new \DateTimeImmutable('2030-03-14T13:00:00Z'), Money::cents(300), [ExternalSales::line('Calcifer', Money::cents(300), 1)]),
            ExternalSales::sumUp('TX-MOUSSE', new \DateTimeImmutable('2030-03-14T14:00:00Z'), Money::cents(300), [ExternalSales::line('Mousse', Money::cents(300), 1)]),
        ]);

        $this->import();

        $prices = array_column(self::getContainer()->get(ListProductsHandler::class)(), 'sellingPrice', 'name');
        self::assertSame(300, $prices['Calcifer'], 'highest price SumUp charged');
        self::assertSame(283, $prices['Mousse'], 'existing products are not modified');
        $orders = array_column(self::getContainer()->get(ListOrdersHandler::class)(), null, 'reference');
        self::assertSame(300, $orders['TX-SINGLE']->total);
        self::assertSame(300, $orders['TX-MOUSSE']->total, 'charged more than the catalogue price');
        self::assertSame(800, $orders['TX-BUNDLE']->total);
    }

    public function testDescriptionIsTheVariant(): void
    {
        $this->scheduleEvent('Salon de printemps', '2030-03-14', '2030-03-15');
        self::createProduct('T-shirt', 2_000, 0, ['S', 'M']);
        self::createProduct('Zine', 1_000);
        self::getContainer()->get(FakeSumUpGateway::class)->willReturn([
            ExternalSales::sumUp('TX-VARIANTS', new \DateTimeImmutable('2030-03-14T12:00:00Z'), Money::cents(10_500), [
                ExternalSales::line('Forêt', Money::cents(1_500), 1, variant: 'A4'),
                ExternalSales::line('Forêt', Money::cents(2_000), 1, variant: 'A3'),
                ExternalSales::line('T-shirt', Money::cents(2_000), 1, variant: 'm'),
                ExternalSales::line('T-shirt', Money::cents(2_000), 1, variant: 'XL'),
                ExternalSales::line('T-shirt S', Money::cents(2_000), 1),
                ExternalSales::line('Zine', Money::cents(1_000), 1, variant: 'Prix normal'),
            ]),
        ]);

        $report = $this->import();

        self::assertSame(1, $report->ordersImported);
        self::assertSame(1, $report->productsCreated);
        $variants = array_column(self::getContainer()->get(ListProductsHandler::class)(), 'variants', 'name');
        self::assertSame(['A4', 'A3'], $variants['Forêt']);
        self::assertSame(['S', 'M', 'XL'], $variants['T-shirt']);
        self::assertSame([], $variants['Zine']);
    }

    public function testProductsSplitPerVariantAreRecognised(): void
    {
        $this->scheduleEvent('Salon de printemps', '2030-03-14', '2030-03-15');
        self::createProduct('Mug Lichen', 1_200);
        self::createProduct('Mug (Fougère)', 1_200);
        self::getContainer()->get(FakeSumUpGateway::class)->willReturn([
            ExternalSales::sumUp('TX-SPLIT', new \DateTimeImmutable('2030-03-14T12:00:00Z'), Money::cents(2_400), [
                ExternalSales::line('Mug', Money::cents(1_200), 1, variant: 'Lichen'),
                ExternalSales::line('Mug', Money::cents(1_200), 1, variant: 'Fougère'),
            ]),
        ]);

        $report = $this->import();

        self::assertSame(1, $report->ordersImported);
        self::assertSame(0, $report->productsCreated);
        self::assertSame([[], []], array_column(self::getContainer()->get(ListProductsHandler::class)(), 'variants'));
    }

    public function testTypedProductsAreMatchedByDisplayName(): void
    {
        $this->scheduleEvent('Salon de printemps', '2030-03-14', '2030-03-15');
        $sticker = (string) self::getContainer()->get(CreateProductTypeHandler::class)('Sticker')->id();
        self::createProduct('Mousse', 400, 60, typeId: $sticker);

        $report = $this->import();

        self::assertSame(2, $report->productsCreated, '"Sticker Mousse" is the existing typed product');
    }

    private function import(): ImportReport
    {
        $report = self::getContainer()->get(ImportSalesHandler::class)('sumup');
        self::getContainer()->get('doctrine')->getManager()->clear();

        return $report;
    }

    private function scheduleEvent(string $name, string $start, string $end): void
    {
        self::getContainer()->get(ScheduleEventHandler::class)(new ScheduleEvent($name, 'Lyon', new \DateTimeImmutable($start), new \DateTimeImmutable($end)));
    }
}
