<?php

declare(strict_types=1);

namespace App\Tests\Functional\Application\Product;

use App\Application\Event\ScheduleEvent\ScheduleEvent;
use App\Application\Event\ScheduleEvent\ScheduleEventHandler;
use App\Application\Order\PlaceOrder\PlaceOrder;
use App\Application\Order\PlaceOrder\PlaceOrderHandler;
use App\Application\Order\RequestedLine;
use App\Application\Product\CreateProduct\CreateProduct;
use App\Application\Product\CreateProduct\CreateProductHandler;
use App\Application\Product\CreateProductType\CreateProductTypeHandler;
use App\Application\Product\ListProducts\ListProductsHandler;
use App\Application\Product\UpdateProduct\UpdateProduct;
use App\Application\Product\UpdateProduct\UpdateProductHandler;
use App\Tests\Support\ActsAsUser;
use App\Tests\Support\CreatesProducts;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Clock\Test\ClockSensitiveTrait;

final class ProductUseCasesTest extends KernelTestCase
{
    use ActsAsUser;
    use CreatesProducts;
    use ClockSensitiveTrait;

    protected function setUp(): void
    {
        self::actAsMemberOf();
    }

    public function testCreateThenListProducts(): void
    {
        $create = self::getContainer()->get(CreateProductHandler::class);
        self::createProduct('T-shirt', 2_000, 800, ['S', 'M']);
        self::createProduct('Aquarelle', 5_000);

        $products = self::getContainer()->get(ListProductsHandler::class)();

        self::assertCount(2, $products);
        self::assertSame('Aquarelle', $products[0]->name);
        self::assertSame(0, $products[0]->buyingPrice);
        self::assertSame(['S', 'M'], $products[1]->variants);
    }

    public function testProductsShowTheirSalesOfTheCurrentYear(): void
    {
        $container = self::getContainer();
        $print = (string) self::createProduct('Print', 1_500, 300, ['A4', 'A3']);
        self::createProduct('Zine', 1_000);
        $container->get(ScheduleEventHandler::class)(new ScheduleEvent('Salon 2027', 'Lyon', new \DateTimeImmutable('2027-03-06'), new \DateTimeImmutable('2027-03-06')));
        $container->get(ScheduleEventHandler::class)(new ScheduleEvent('Salon 2028', 'Lyon', new \DateTimeImmutable('2028-03-04'), new \DateTimeImmutable('2028-03-04')));
        $place = $container->get(PlaceOrderHandler::class);
        $place(new PlaceOrder(new \DateTimeImmutable('2027-03-06 12:00'), [new RequestedLine($print, 'A4', 2), new RequestedLine($print, 'A3', 1)]));
        $place(new PlaceOrder(new \DateTimeImmutable('2028-03-04 12:00'), [new RequestedLine($print, 'A4', 5)]));
        $container->get('doctrine')->getManager()->clear();

        self::mockTime('2027-06-01 10:00');
        $products = array_column($container->get(ListProductsHandler::class)(), null, 'name');

        self::assertSame(2027, $products['Print']->salesYear);
        self::assertSame(3, $products['Print']->unitsSold);
        self::assertSame(4_500, $products['Print']->sales);
        self::assertSame(0, $products['Zine']->unitsSold);
    }

    public function testReferenceIsGeneratedFromTypeAndNameAndMadeUnique(): void
    {
        $print = (string) self::getContainer()->get(CreateProductTypeHandler::class)('Print')->id();
        $create = self::getContainer()->get(CreateProductHandler::class);
        self::createProduct('Forêt', 1_500, typeId: $print);
        self::createProduct('Forêt', 1_500, typeId: $print);
        self::createProduct('Clairière', 12_000);

        $references = array_column(self::getContainer()->get(ListProductsHandler::class)(), 'reference', 'displayName');

        self::assertSame('PRD-CLAIRIERE', $references['Clairière']);
        self::assertEqualsCanonicalizing(['PRD-CLAIRIERE', 'PRI-FORET', 'PRI-FORET-2'], array_column(self::getContainer()->get(ListProductsHandler::class)(), 'reference'));
    }

    public function testUpdateKeepsTheReference(): void
    {
        $id = self::createProduct('T-shirt', 2_000, 900, ['S']);

        self::getContainer()->get(UpdateProductHandler::class)(new UpdateProduct((string) $id, 'T-shirt bio', 2_500, ['S', 'M']));
        self::getContainer()->get('doctrine')->getManager()->clear();

        $product = self::getContainer()->get(ListProductsHandler::class)()[0];
        self::assertSame('PRD-T-SHIRT', $product->reference);
        self::assertSame('T-shirt bio', $product->name);
        self::assertSame(2_500, $product->sellingPrice);
        self::assertSame(900, $product->buyingPrice, 'editing a product never changes its buying price');
        self::assertSame(['S', 'M'], $product->variants);
    }
}
