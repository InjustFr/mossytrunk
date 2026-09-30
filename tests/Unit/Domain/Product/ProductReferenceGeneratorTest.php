<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Product;

use App\Domain\Product\Product;
use App\Domain\Product\ProductReferenceGenerator;
use App\Domain\Product\ProductRepository;
use App\Domain\Product\ProductType;
use App\Domain\Shared\Money;
use App\Tests\Support\TestWorkspace;
use PHPUnit\Framework\TestCase;

final class ProductReferenceGeneratorTest extends TestCase
{
    public function testTypeCodeThenFirstThreeLettersOfTheNameWithAUniqueSuffix(): void
    {
        $existing = ['PRI-FOR' => true];
        $repository = $this->createStub(ProductRepository::class);
        $repository->method('findByReference')->willReturnCallback(
            static fn (string $reference): ?Product => isset($existing[$reference]) ? Product::create(TestWorkspace::get(), $reference, 'x', Money::zero()) : null,
        );
        $generator = new ProductReferenceGenerator($repository);
        $print = ProductType::create(TestWorkspace::get(), 'Print', 'PRI');

        self::assertSame('PRI-FOR-2', $generator->generate($print, 'Forêt'));
        self::assertSame('PRI-FOR-3', $generator->generate($print, "Forêt d'automne"), 'references handed out earlier in the request are reserved');
        self::assertSame('PRD-CLA', $generator->generate(null, '« Clairière »'));
        self::assertSame('PRI-X', $generator->generate($print, '!!'));
    }
}
