<?php

declare(strict_types=1);

namespace App\Tests\Functional\Application\Product;

use App\Application\Product\CreateProduct\CreateProduct;
use App\Application\Product\CreateProduct\CreateProductHandler;
use App\Application\Product\CreateProductType\CreateProductTypeHandler;
use App\Application\Product\ListProducts\ListProductsHandler;
use App\Application\Product\UpdateProduct\UpdateProduct;
use App\Application\Product\UpdateProduct\UpdateProductHandler;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ProductUseCasesTest extends KernelTestCase
{
    public function testCreateThenListProducts(): void
    {
        $create = self::getContainer()->get(CreateProductHandler::class);
        $create(new CreateProduct('T-shirt', 2_000, 800, ['S', 'M']));
        $create(new CreateProduct('Aquarelle', 5_000));

        $products = self::getContainer()->get(ListProductsHandler::class)();

        self::assertCount(2, $products);
        self::assertSame('Aquarelle', $products[0]->name);
        self::assertSame(0, $products[0]->buyingPrice);
        self::assertSame(['S', 'M'], $products[1]->variants);
    }

    public function testReferenceIsGeneratedFromTypeAndNameAndMadeUnique(): void
    {
        $print = (string) self::getContainer()->get(CreateProductTypeHandler::class)('Print')->id();
        $create = self::getContainer()->get(CreateProductHandler::class);
        $create(new CreateProduct('Forêt', 1_500, typeId: $print));
        $create(new CreateProduct('Forêt', 1_500, typeId: $print));
        $create(new CreateProduct('Clairière', 12_000));

        $references = array_column(self::getContainer()->get(ListProductsHandler::class)(), 'reference', 'displayName');

        self::assertSame('PRD-CLAIRIERE', $references['Clairière']);
        self::assertEqualsCanonicalizing(['PRD-CLAIRIERE', 'PRI-FORET', 'PRI-FORET-2'], array_values(array_column(self::getContainer()->get(ListProductsHandler::class)(), 'reference')));
    }

    public function testUpdateKeepsTheReference(): void
    {
        $id = self::getContainer()->get(CreateProductHandler::class)(new CreateProduct('T-shirt', 2_000, 0, ['S']));

        self::getContainer()->get(UpdateProductHandler::class)(new UpdateProduct((string) $id, 'T-shirt bio', 2_500, 900, ['S', 'M']));
        self::getContainer()->get('doctrine')->getManager()->clear();

        $product = self::getContainer()->get(ListProductsHandler::class)()[0];
        self::assertSame('PRD-T-SHIRT', $product->reference);
        self::assertSame('T-shirt bio', $product->name);
        self::assertSame(2_500, $product->sellingPrice);
        self::assertSame(900, $product->buyingPrice);
        self::assertSame(['S', 'M'], $product->variants);
    }
}
