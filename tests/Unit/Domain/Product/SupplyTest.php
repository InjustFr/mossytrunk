<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Product;

use App\Domain\Product\Exception\SupplyIsNotSold;
use App\Domain\Product\Product;
use App\Domain\Product\ProductKind;
use App\Domain\Product\ProductType;
use App\Domain\Sales\SalesChannel;
use App\Domain\Shared\Money;
use App\Tests\Support\TestProductType;
use App\Tests\Support\TestWorkspace;
use PHPUnit\Framework\TestCase;

final class SupplyTest extends TestCase
{
    public function testASupplyIsStockedButHasNoPrice(): void
    {
        $sleeve = $this->sleeve();

        self::assertSame([ProductKind::Supply, 0], [$sleeve->kind(), $sleeve->sellingPrice()->amount()]);
        self::assertSame('Grande', $sleeve->sellable('Grande')->variant);
        $sleeve->reprice(Money::zero());

        $this->expectException(SupplyIsNotSold::class);
        $sleeve->reprice(Money::cents(10));
    }

    public function testASupplyIsNeverSold(): void
    {
        $this->expectException(SupplyIsNotSold::class);
        $this->sleeve()->sellableOn(null, 'Grande');
    }

    public function testASupplyHasNoChannelPrice(): void
    {
        $this->expectException(SupplyIsNotSold::class);
        $this->sleeve()->setPriceOn(SalesChannel::open(TestWorkspace::get(), 'Etsy'), Money::zero());
    }

    public function testAProductIsAnArticleByDefault(): void
    {
        self::assertFalse(Product::create(TestWorkspace::get(), 'P-1', 'Forêt', Money::cents(1_500), TestProductType::get())->isSupply());
    }

    private function sleeve(): Product
    {
        return Product::supply(TestWorkspace::get(), 'F-1', 'Pochette', ProductType::create(TestWorkspace::get(), 'Emballage', 'EMB'), ['Petite', 'Grande']);
    }
}
