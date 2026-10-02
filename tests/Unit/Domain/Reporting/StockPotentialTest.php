<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Reporting;

use App\Domain\Product\Product;
use App\Domain\Product\ProductType;
use App\Domain\Reporting\StockPotential;
use App\Domain\Shared\Money;
use App\Domain\Stock\LotOrigin;
use App\Domain\Stock\StockItem;
use App\Tests\Support\TestWorkspace;
use PHPUnit\Framework\TestCase;

final class StockPotentialTest extends TestCase
{
    public function testTheStockLeftIsWorthItsSellingPriceLessUrssafAndWhatItCost(): void
    {
        $print = Product::create(TestWorkspace::get(), 'PRT', 'Forêt', Money::cents(1_500), ProductType::create(TestWorkspace::get(), 'Print', 'PRI'), ['A5', 'A4']);
        $a5 = $this->stock($print, 'A5', 4, 1_200);
        $a4 = $this->stock($print, 'A4', 1, 400);
        $a4->withdraw(3, Money::cents(400));

        $potential = StockPotential::of($print, [$a5, $a4]);

        self::assertSame(['units' => 4, 'turnover' => 6_000, 'stockCost' => 1_200, 'urssaf' => 768, 'revenue' => 4_032], $potential->toArray());
    }

    public function testSuppliesAndForgottenVariantsAreNotForSale(): void
    {
        $type = ProductType::create(TestWorkspace::get(), 'Emballage', 'EMB');
        $sleeve = Product::supply(TestWorkspace::get(), 'POC', 'Pochette', $type);
        $tote = Product::create(TestWorkspace::get(), 'TOT', 'Tote', Money::cents(1_500), $type, ['Noir']);
        $natural = $this->stock($tote, 'Noir', 2, 1_000);
        $tote->removeVariant('Noir');

        self::assertSame(0, StockPotential::of($sleeve, [$this->stock($sleeve, null, 50, 500)])->units);
        self::assertSame(0, StockPotential::of($tote, [$natural])->units);
    }

    private function stock(Product $product, ?string $variant, int $quantity, int $paid): StockItem
    {
        $stock = StockItem::open($product, $variant);
        $stock->receive($quantity, Money::cents($paid), LotOrigin::Purchase, new \DateTimeImmutable('2026-07-01'));

        return $stock;
    }
}
