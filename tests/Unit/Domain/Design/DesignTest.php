<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Design;

use App\Domain\Design\Design;
use App\Domain\Design\DesignStatus;
use App\Domain\Design\Gabarit;
use App\Domain\Design\InvalidDesign;
use App\Domain\Product\ProductType;
use App\Domain\Shared\Money;
use App\Tests\Support\TestWorkspace;
use PHPUnit\Framework\TestCase;

final class DesignTest extends TestCase
{
    private Gabarit $print;
    private Gabarit $glossy;

    protected function setUp(): void
    {
        $this->print = Gabarit::create(TestWorkspace::get(), 'Tirage 15×15', ProductType::create(TestWorkspace::get(), 'Print', 'PRI'), Money::cents(1_200), Money::cents(300), [], ['Recadrage carré', 'Fond perdu 3 mm']);
        $this->glossy = Gabarit::create(TestWorkspace::get(), 'Sticker brillant', ProductType::create(TestWorkspace::get(), 'Sticker', 'STI'), Money::cents(400), Money::cents(60), ['5 cm', '8 cm'], ['Détourage']);
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

    public function testValidatedDesignIsFrozenAndNoLongerCurrent(): void
    {
        $design = $this->readyDesign();

        $declinations = $design->validate(new \DateTimeImmutable());

        self::assertCount(2, $declinations);
        self::assertSame(DesignStatus::Validated, $design->status());
        self::assertFalse($design->isCurrent());
        $this->expectException(InvalidDesign::class);
        $design->decline(Gabarit::create(TestWorkspace::get(), 'Carte', null, Money::cents(300), Money::cents(50)));
    }

    public function testTwoDeclinationsCannotMakeTheSameProduct(): void
    {
        $mat = Gabarit::create(TestWorkspace::get(), 'Sticker mat', $this->glossy->type(), Money::cents(400), Money::cents(60));
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

        Gabarit::create(TestWorkspace::get(), 'Carte', null, Money::zero(), Money::zero(), [], ['Recto', 'Recto']);
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
