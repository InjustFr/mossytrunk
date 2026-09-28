<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Product;

use App\Domain\Product\InvalidProduct;
use App\Domain\Product\Product;
use App\Domain\Product\ProductType;
use App\Domain\Shared\Money;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ProductTypeTest extends TestCase
{
    /**
     * @return iterable<array{string, string}>
     */
    public static function codes(): iterable
    {
        yield ['Print', 'PRI'];
        yield ['T-shirt', 'TSH'];
        yield ['Épingle', 'EPI'];
        yield ['A', 'A'];
        yield ['!!', 'TYP'];
    }

    #[DataProvider('codes')]
    public function testCodeIsDerivedFromName(string $name, string $code): void
    {
        self::assertSame($code, ProductType::codeFor($name));
    }

    public function testNameIsRequired(): void
    {
        $this->expectException(InvalidProduct::class);

        ProductType::create('  ', 'PRI');
    }

    public function testCodeFormat(): void
    {
        $this->expectException(InvalidProduct::class);

        ProductType::create('Print', 'pri-1');
    }

    public function testTypedProductIsDisplayedAsTypeThenName(): void
    {
        $print = ProductType::create('Print', 'PRI');
        $product = Product::create('PRI-FORET', 'Forêt', Money::cents(1_500), variants: ['A4'], type: $print);

        self::assertSame('Print Forêt', $product->displayName());
        self::assertSame('Print Forêt', $product->sellable('A4')->productName);
        self::assertSame('Print Forêt — A4', $product->sellable('A4')->label());

        $print->rename('Affiche');
        self::assertSame('Affiche Forêt', $product->displayName());

        $product->classify(null);
        self::assertSame('Forêt', $product->displayName());
    }
}
