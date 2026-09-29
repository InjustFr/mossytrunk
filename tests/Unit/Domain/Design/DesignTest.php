<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Design;

use App\Domain\Design\Design;
use App\Domain\Design\DesignStatus;
use App\Domain\Design\Gabarit;
use App\Domain\Design\InvalidDesign;
use App\Domain\Product\Product;
use App\Domain\Product\ProductType;
use App\Domain\Shared\Money;
use App\Tests\Support\TestWorkspace;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Ulid;

final class DesignTest extends TestCase
{
    private Gabarit $print;
    private Gabarit $glossy;

    protected function setUp(): void
    {
        $this->print = Gabarit::create(TestWorkspace::get(), 'Tirage 15×15', ProductType::create(TestWorkspace::get(), 'Print', 'PRI'), Money::cents(1_200), [], ['Recadrage carré', 'Fond perdu 3 mm']);
        $this->glossy = Gabarit::create(TestWorkspace::get(), 'Sticker brillant', ProductType::create(TestWorkspace::get(), 'Sticker', 'STI'), Money::cents(400), ['5 cm', '8 cm'], ['Détourage']);
    }

    public function testADeclinationStartsFromItsGabarit(): void
    {
        $design = Design::start(TestWorkspace::get(), 'Forêt');

        $declination = $design->decline($this->glossy);

        self::assertSame('Sticker Forêt', $declination->displayName());
        self::assertSame(400, $declination->sellingPrice()->amount());
        self::assertSame(['5 cm', '8 cm'], $declination->variants());
        self::assertSame(['Détourage'], $declination->pendingAdaptations());
        self::assertTrue($design->isCurrent());
    }

    public function testADesignIsDeclinedOncePerGabarit(): void
    {
        $design = Design::start(TestWorkspace::get(), 'Forêt');
        $design->decline($this->print);

        $this->expectException(InvalidDesign::class);

        $design->decline($this->print);
    }

    public function testValidationNeedsEveryAdaptationDone(): void
    {
        $design = Design::start(TestWorkspace::get(), 'Forêt');
        $declination = $design->decline($this->print);
        $design->tick($declination->id(), 'Recadrage carré', true);

        $this->expectExceptionMessage('« Print Forêt » a encore 1 adaptation à faire.');

        $design->validate(new \DateTimeImmutable());
    }

    public function testUntickingAnAdaptationMakesItPendingAgain(): void
    {
        $design = Design::start(TestWorkspace::get(), 'Forêt');
        $declination = $design->decline($this->glossy);
        $design->tick($declination->id(), 'Détourage', true);
        $design->tick($declination->id(), 'Détourage', false);

        self::assertSame(['Détourage'], $declination->pendingAdaptations());
    }

    public function testValidationNeedsADeclination(): void
    {
        $this->expectException(InvalidDesign::class);

        Design::start(TestWorkspace::get(), 'Forêt')->validate(new \DateTimeImmutable());
    }

    public function testValidationFreezesTheProducedDeclinationsAndLeavesTheBench(): void
    {
        $design = $this->readyDesign();

        $declinations = $design->validate(new \DateTimeImmutable());
        foreach ($declinations as $declination) {
            $declination->linkProduct(new Ulid());
        }

        self::assertCount(2, $declinations);
        self::assertSame(DesignStatus::Validated, $design->status());
        self::assertFalse($design->isCurrent());
        $this->expectException(InvalidDesign::class);
        $design->tick($declinations[0]->id(), 'Recadrage carré', false);
    }

    public function testAValidatedDesignCanBeDeclinedAgainAndOnlyTheNewDeclinationIsProduced(): void
    {
        $design = $this->readyDesign();
        foreach ($design->validate(new \DateTimeImmutable()) as $declination) {
            $declination->linkProduct(new Ulid());
        }

        $card = $design->decline(Gabarit::create(TestWorkspace::get(), 'Carte', null, Money::cents(300)));

        self::assertSame(DesignStatus::InProgress, $design->status());
        self::assertTrue($design->isCurrent());
        self::assertSame([$card], $design->validate(new \DateTimeImmutable()));
    }

    public function testAnExistingProductBecomesAFinishedDesign(): void
    {
        $product = Product::create(TestWorkspace::get(), 'STI-FORET', 'Forêt', Money::cents(450), ['5 cm'], $this->glossy->type());

        $design = Design::fromProduct(TestWorkspace::get(), $product, $this->glossy, null, new \DateTimeImmutable());

        self::assertSame('Forêt', $design->name());
        self::assertSame(DesignStatus::Validated, $design->status());
        self::assertFalse($design->isCurrent());
        $declination = $design->declinations()[0];
        self::assertTrue($declination->productId()->equals($product->id()));
        self::assertSame(450, $declination->sellingPrice()->amount());
        self::assertSame([], $declination->pendingAdaptations());
        self::assertTrue($design->hasProducts());
    }

    public function testTwoDeclinationsCannotMakeTheSameProduct(): void
    {
        $mat = Gabarit::create(TestWorkspace::get(), 'Sticker mat', $this->glossy->type(), Money::cents(400));
        $design = Design::start(TestWorkspace::get(), 'Forêt');
        $design->decline($mat);
        $glossy = $design->decline($this->glossy);
        $design->tick($glossy->id(), 'Détourage', true);

        $this->expectException(InvalidDesign::class);

        $design->validate(new \DateTimeImmutable());
    }

    public function testGabaritAdaptationsAreUnique(): void
    {
        $this->expectException(InvalidDesign::class);

        Gabarit::create(TestWorkspace::get(), 'Carte', null, Money::zero(), [], ['Recto', 'Recto']);
    }

    private function readyDesign(): Design
    {
        $design = Design::start(TestWorkspace::get(), 'Forêt');
        $print = $design->decline($this->print);
        $sticker = $design->decline($this->glossy);
        foreach ([[$print, ['Recadrage carré', 'Fond perdu 3 mm']], [$sticker, ['Détourage']]] as [$declination, $adaptations]) {
            foreach ($adaptations as $adaptation) {
                $design->tick($declination->id(), $adaptation, true);
            }
        }

        return $design;
    }
}
