<?php

declare(strict_types=1);

namespace App\Tests\Functional\Application\Product;

use App\Application\Product\CreateProduct\CreateProductHandler;
use App\Application\Product\CreateProductType\CreateProductTypeHandler;
use App\Application\Product\ListProducts\ListProductsHandler;
use App\Application\Product\ListProductTypes\ListProductTypesHandler;
use App\Application\Product\SuggestTypeCode\SuggestTypeCodeHandler;
use App\Application\Product\UpdateProduct\UpdateProduct;
use App\Application\Product\UpdateProduct\UpdateProductHandler;
use App\Application\Product\UpdateProductType\UpdateProductTypeHandler;
use App\Domain\Product\Exception\InvalidProduct;
use App\Domain\Product\ProductType;
use App\Tests\Support\ActsAsUser;
use App\Tests\Support\CreatesProducts;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ProductTypeUseCasesTest extends KernelTestCase
{
    use ActsAsUser;
    use CreatesProducts;

    protected function setUp(): void
    {
        self::actAsMemberOf();
    }

    public function testCodesAreUnique(): void
    {
        $create = self::getContainer()->get(CreateProductTypeHandler::class);
        $create('Print');
        $create('Pringles');

        $types = self::getContainer()->get(ListProductTypesHandler::class)();

        self::assertSame(['Pringles' => 'PRI2', 'Print' => 'PRI'], array_column($types, 'code', 'name'));
    }

    public function testCodeIsChosenAtCreationAndChangedLater(): void
    {
        $create = self::getContainer()->get(CreateProductTypeHandler::class);
        $print = (string) $create('Print', code: 'aff')->id();
        $create('Sticker');

        self::getContainer()->get(UpdateProductTypeHandler::class)($print, 'Print', '#2f7f7a', 'PRT');

        $types = self::getContainer()->get(ListProductTypesHandler::class)();
        self::assertSame(['Print' => 'PRT', 'Sticker' => 'STI'], array_column($types, 'code', 'name'));
    }

    public function testChosenCodeIsUnique(): void
    {
        $create = self::getContainer()->get(CreateProductTypeHandler::class);
        $create('Print');
        $sticker = (string) $create('Sticker')->id();

        try {
            $create('Affiche', code: 'PRI');
            self::fail('a used code is refused at creation');
        } catch (InvalidProduct) {
        }

        $this->expectException(InvalidProduct::class);
        self::getContainer()->get(UpdateProductTypeHandler::class)($sticker, 'Sticker', '#2f7f7a', 'pri');
    }

    public function testCodeSuggestionAvoidsTakenCodes(): void
    {
        self::getContainer()->get(CreateProductTypeHandler::class)('Print');

        self::assertSame('PRI2', self::getContainer()->get(SuggestTypeCodeHandler::class)('Pringles'));
    }

    public function testColorIsChosenOrTakenFromThePalette(): void
    {
        $create = self::getContainer()->get(CreateProductTypeHandler::class);
        $create('Print');
        $create('Sticker');
        $create('Zine', '#A3485A');

        $types = self::getContainer()->get(ListProductTypesHandler::class)();

        self::assertSame(
            ['Print' => ProductType::PALETTE[0], 'Sticker' => ProductType::PALETTE[1], 'Zine' => '#a3485a'],
            array_column($types, 'color', 'name'),
        );
    }

    public function testNameAndColorAreEdited(): void
    {
        $print = (string) self::getContainer()->get(CreateProductTypeHandler::class)('Print')->id();

        self::getContainer()->get(UpdateProductTypeHandler::class)($print, 'Affiche', '#2f7f7a');

        $types = self::getContainer()->get(ListProductTypesHandler::class)();
        self::assertSame(['Affiche' => '#2f7f7a'], array_column($types, 'color', 'name'));
    }

    public function testEditedNameStaysUnique(): void
    {
        $create = self::getContainer()->get(CreateProductTypeHandler::class);
        $create('Print');
        $sticker = (string) $create('Sticker')->id();

        $this->expectException(InvalidProduct::class);
        self::getContainer()->get(UpdateProductTypeHandler::class)($sticker, 'PRINT', '#2f7f7a');
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
        self::createProduct('Mousse', 400, typeId: $sticker);
        $forest = self::createProduct('Forêt', 1_500, typeId: $print);
        self::createProduct('Aquarelle', 12_000);

        $products = self::getContainer()->get(ListProductsHandler::class)();
        self::assertSame(['Print Forêt', 'Sticker Mousse', 'Aquarelle'], array_column($products, 'displayName'));
        self::assertSame('Print', $products[0]->typeName);

        self::getContainer()->get(UpdateProductTypeHandler::class)($print, 'Affiche', '#4f6d8f');
        self::getContainer()->get(UpdateProductHandler::class)(new UpdateProduct((string) $forest, 'Forêt', 1_500, [], $sticker));
        self::getContainer()->get('doctrine')->getManager()->clear();

        $products = self::getContainer()->get(ListProductsHandler::class)();
        self::assertContains('Sticker Forêt', array_column($products, 'displayName'));
    }
}
