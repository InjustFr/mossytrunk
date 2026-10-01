<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Product;

use App\Domain\Product\Product;
use App\Domain\Sales\SalesChannel;
use App\Domain\Shared\Exception\InvalidMoney;
use App\Domain\Shared\Money;
use App\Tests\Support\TestProductType;
use App\Tests\Support\TestWorkspace;
use PHPUnit\Framework\TestCase;

final class ChannelPriceTest extends TestCase
{
    public function testAChannelFollowsTheSellingPriceUntilItGetsItsOwn(): void
    {
        $product = $this->product();
        $etsy = SalesChannel::open(TestWorkspace::get(), 'Etsy', service: 'etsy');

        self::assertSame(1_500, $product->priceOn($etsy)->amount());

        $product->setPriceOn($etsy, Money::cents(1_800));
        $product->reprice(Money::cents(1_600));
        self::assertSame([1_800, 1_600, 1_600], [$product->priceOn($etsy)->amount(), $product->priceOn(null)->amount(), $product->sellingPrice()->amount()]);

        $product->setPriceOn($etsy, Money::cents(1_900));
        self::assertCount(1, $product->channelPrices());
        self::assertSame(1_900, $product->priceOn($etsy)->amount());

        $product->followSellingPriceOn($etsy);
        self::assertSame([], $product->channelPrices());
        self::assertSame(1_600, $product->priceOn($etsy)->amount());
    }

    public function testTheChannelPriceIsTheListedPriceOfItsSales(): void
    {
        $product = $this->product(['A4']);
        $etsy = SalesChannel::open(TestWorkspace::get(), 'Etsy');
        $product->setPriceOn($etsy, Money::cents(2_000));

        $item = $product->sellableOn($etsy, 'a4');

        self::assertSame(['A4', 2_000], [$item->variant, $item->sellingPrice->amount()]);
        self::assertSame(1_500, $product->sellableOn(null, 'A4')->sellingPrice->amount());
    }

    public function testTheMainChannelPriceIsTheSellingPrice(): void
    {
        $product = $this->product();
        $markets = SalesChannel::main(TestWorkspace::get(), 'Marchés');

        $product->setPriceOn($markets, Money::cents(1_700));

        self::assertSame([1_700, 1_700], [$product->sellingPrice()->amount(), $product->priceOn($markets)->amount()]);
        self::assertSame([], $product->channelPrices());
    }

    public function testAChannelPriceIsNeverNegative(): void
    {
        $this->expectException(InvalidMoney::class);

        $this->product()->setPriceOn(SalesChannel::open(TestWorkspace::get(), 'Etsy'), Money::cents(-1));
    }

    /**
     * @param list<string> $variants
     */
    private function product(array $variants = []): Product
    {
        return Product::create(TestWorkspace::get(), 'PRI-FOR', 'Forêt', Money::cents(1_500), TestProductType::get(), $variants);
    }
}
