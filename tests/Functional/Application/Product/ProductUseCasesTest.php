<?php

declare(strict_types=1);

namespace App\Tests\Functional\Application\Product;

use App\Application\Product\CreateProduct\CreateProduct;
use App\Application\Product\CreateProduct\CreateProductHandler;
use App\Application\Product\ListProducts\ListProductsHandler;
use App\Application\Product\UpdateProduct\UpdateProduct;
use App\Application\Product\UpdateProduct\UpdateProductHandler;
use App\Domain\Product\InvalidProduct;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ProductUseCasesTest extends KernelTestCase
{
    public function testCreateThenListProducts(): void
    {
        $create = self::getContainer()->get(CreateProductHandler::class);
        $create(new CreateProduct('TS-01', 'T-shirt', 2_000, 800, ['S', 'M']));
        $create(new CreateProduct('ART-01', 'Aquarelle', 5_000));

        $products = self::getContainer()->get(ListProductsHandler::class)();

        self::assertCount(2, $products);
        self::assertSame('Aquarelle', $products[0]->name);
        self::assertSame(0, $products[0]->buyingPrice);
        self::assertSame(['S', 'M'], $products[1]->variants);
    }

    public function testReferenceMustBeUnique(): void
    {
        $create = self::getContainer()->get(CreateProductHandler::class);
        $create(new CreateProduct('TS-01', 'T-shirt', 2_000));

        $this->expectException(InvalidProduct::class);
        $create(new CreateProduct('TS-01', 'Autre', 1_000));
    }

    public function testUpdateProduct(): void
    {
        $id = self::getContainer()->get(CreateProductHandler::class)(new CreateProduct('TS-01', 'T-shirt', 2_000, 0, ['S']));

        self::getContainer()->get(UpdateProductHandler::class)(new UpdateProduct((string) $id, 'TS-02', 'T-shirt bio', 2_500, 900, ['S', 'M']));
        self::getContainer()->get('doctrine')->getManager()->clear();

        $product = self::getContainer()->get(ListProductsHandler::class)()[0];
        self::assertSame('TS-02', $product->reference);
        self::assertSame('T-shirt bio', $product->name);
        self::assertSame(2_500, $product->sellingPrice);
        self::assertSame(900, $product->buyingPrice);
        self::assertSame(['S', 'M'], $product->variants);
    }

    public function testUpdateCannotStealAnotherReference(): void
    {
        $create = self::getContainer()->get(CreateProductHandler::class);
        $create(new CreateProduct('TS-01', 'T-shirt', 2_000));
        $id = $create(new CreateProduct('ART-01', 'Aquarelle', 5_000));

        $this->expectException(InvalidProduct::class);
        self::getContainer()->get(UpdateProductHandler::class)(new UpdateProduct((string) $id, 'TS-01', 'Aquarelle', 5_000, 0, []));
    }
}
