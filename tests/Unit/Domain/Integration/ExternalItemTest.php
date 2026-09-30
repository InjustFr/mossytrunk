<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Integration;

use App\Domain\Integration\ExternalItem;
use App\Domain\Product\Product;
use App\Domain\Shared\Money;
use App\Tests\Support\TestProductType;
use App\Tests\Support\TestWorkspace;
use PHPUnit\Framework\TestCase;

final class ExternalItemTest extends TestCase
{
    public function testTheKeyIgnoresTheCaseAndSpacesOfTheVariation(): void
    {
        self::assertSame(ExternalItem::keyOf('7', ' Noir '), ExternalItem::keyOf('7', 'noir'));
        self::assertNotSame(ExternalItem::keyOf('7', 'Noir'), ExternalItem::keyOf('7', null));
    }

    public function testASeenItemWaitsToBeLinkedThenRemembersItsProduct(): void
    {
        $item = ExternalItem::seen(TestWorkspace::get(), 'etsy', '7', 'Tote bag', 'Noir', new \DateTimeImmutable('2030-01-01'));
        self::assertFalse($item->isLinked());

        $tote = Product::create(TestWorkspace::get(), 'TOTE', 'Tote', Money::cents(1_500), TestProductType::get(), ['Noir', 'Vert']);
        $item->link($tote->sellable('Noir'));

        self::assertTrue($item->isLinked());
        self::assertEquals($tote->id(), $item->productId());
        self::assertSame('Noir', $item->variant());
        self::assertSame(['etsy', '7|noir'], [$item->service(), $item->itemKey()]);
    }

    public function testSeeingItAgainKeepsTheLatestLabel(): void
    {
        $item = ExternalItem::seen(TestWorkspace::get(), 'sumup', 'forêt', 'Forêt', null, new \DateTimeImmutable('2030-01-01'));

        $item->seenAgain('Forêt (nouveau)', new \DateTimeImmutable('2030-02-01'));

        self::assertSame('Forêt (nouveau)', $item->label());
        self::assertEquals(new \DateTimeImmutable('2030-02-01'), $item->seenAt());
    }
}
