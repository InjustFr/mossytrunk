<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Product;

use App\Domain\Product\Product;
use App\Domain\Product\ProductReferenceGenerator;
use App\Domain\Product\ProductRepository;
use App\Domain\Product\ProductType;
use App\Domain\Shared\Money;
use PHPUnit\Framework\TestCase;

final class ProductReferenceGeneratorTest extends TestCase
{
    public function testSlug(): void
    {
        self::assertSame('FORET-D-AUTOMNE', ProductReferenceGenerator::slug("Forêt d'automne"));
        self::assertSame('CLAIRIERE', ProductReferenceGenerator::slug('« Clairière »'));
        self::assertSame('X', ProductReferenceGenerator::slug('!!'));
        self::assertSame(40, \strlen(ProductReferenceGenerator::slug(str_repeat('a', 60))));
    }

    public function testTypeCodePrefixAndUniqueSuffix(): void
    {
        $existing = ['PRI-FORET' => true];
        $repository = $this->createStub(ProductRepository::class);
        $repository->method('findByReference')->willReturnCallback(
            static fn (string $reference): ?Product => isset($existing[$reference]) ? Product::create($reference, 'x', Money::zero()) : null,
        );
        $generator = new ProductReferenceGenerator($repository);
        $print = ProductType::create('Print', 'PRI');

        self::assertSame('PRI-FORET-2', $generator->generate($print, 'Forêt'));
        self::assertSame('PRI-FORET-3', $generator->generate($print, 'Forêt'), 'references handed out earlier in the request are reserved');
        self::assertSame('PRD-FORET', $generator->generate(null, 'Forêt'));
    }
}
