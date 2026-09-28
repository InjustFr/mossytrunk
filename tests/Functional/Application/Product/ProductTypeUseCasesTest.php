<?php

declare(strict_types=1);

namespace App\Tests\Functional\Application\Product;

use App\Application\Product\CreateProduct\CreateProduct;
use App\Application\Product\CreateProduct\CreateProductHandler;
use App\Application\Product\CreateProductType\CreateProductTypeHandler;
use App\Application\Product\ListProducts\ListProductsHandler;
use App\Application\Product\ListProductTypes\ListProductTypesHandler;
use App\Application\Product\RenameProductType\RenameProductTypeHandler;
use App\Application\Product\UpdateProduct\UpdateProduct;
use App\Application\Product\UpdateProduct\UpdateProductHandler;
use App\Domain\Product\InvalidProduct;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ProductTypeUseCasesTest extends KernelTestCase
{
    public function testCodesAreUnique(): void
    {
        $create = self::getContainer()->get(CreateProductTypeHandler::class);
        $create('Print');
        $create('Pringles');

        $types = self::getContainer()->get(ListProductTypesHandler::class)();

        self::assertSame(['Pringles' => 'PRI2', 'Print' => 'PRI'], array_column($types, 'code', 'name'));
    }

    public function testNamesAreUniqueCaseInsensitive(): void
    {
        self::getContainer()->get(CreateProductTypeHandler::class)('Print');

        $this->expectException(InvalidProduct::class);
        self::getContainer()->get(CreateProductTypeHandler::class)(' print ');
    }

    public function testProductsCarryTheirTypeAndAreSortedByTypeThenName(): void
    {
        $print = (string) self::getContainer()->get(CreateProductTypeHandler::class)('Print')->id();
        $sticker = (string) self::getContainer()->get(CreateProductTypeHandler::class)('Sticker')->id();
        $create = self::getContainer()->get(CreateProductHandler::class);
        $create(new CreateProduct('Mousse', 400, typeId: $sticker));
        $forest = $create(new CreateProduct('Forêt', 1_500, typeId: $print));
        $create(new CreateProduct('Aquarelle', 12_000));

        $products = self::getContainer()->get(ListProductsHandler::class)();
        self::assertSame(['Print Forêt', 'Sticker Mousse', 'Aquarelle'], array_column($products, 'displayName'));
        self::assertSame('Print', $products[0]->typeName);

        self::getContainer()->get(RenameProductTypeHandler::class)($print, 'Affiche');
        self::getContainer()->get(UpdateProductHandler::class)(new UpdateProduct((string) $forest, 'Forêt', 1_500, 0, [], $sticker));
        self::getContainer()->get('doctrine')->getManager()->clear();

        $products = self::getContainer()->get(ListProductsHandler::class)();
        self::assertContains('Sticker Forêt', array_column($products, 'displayName'));
    }
}
