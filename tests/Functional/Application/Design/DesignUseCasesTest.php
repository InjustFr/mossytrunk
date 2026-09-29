<?php

declare(strict_types=1);

namespace App\Tests\Functional\Application\Design;

use App\Application\Design\AdjustDeclination\AdjustDeclination;
use App\Application\Design\AdjustDeclination\AdjustDeclinationHandler;
use App\Application\Design\DeclineDesign\DeclineDesignHandler;
use App\Application\Design\DesignProduct\DesignProductHandler;
use App\Application\Design\DesignView;
use App\Application\Design\GetDesign\GetDesignHandler;
use App\Application\Design\ListDesigns\ListDesignsHandler;
use App\Application\Design\SaveCollection\SaveCollectionHandler;
use App\Application\Design\SaveDesign\SaveDesign;
use App\Application\Design\SaveDesign\SaveDesignHandler;
use App\Application\Design\SaveGabarit\SaveGabarit;
use App\Application\Design\SaveGabarit\SaveGabaritHandler;
use App\Application\Design\TickAdaptation\TickAdaptationHandler;
use App\Application\Design\ValidateDesign\ValidateDesignHandler;
use App\Application\Design\WorkOn\WorkOnHandler;
use App\Application\Product\CreateProductType\CreateProductTypeHandler;
use App\Application\Product\GetProduct\GetProductHandler;
use App\Application\Product\ListProducts\ListProductsHandler;
use App\Application\Product\ListProducts\ProductView;
use App\Domain\Design\InvalidDesign;
use App\Domain\Shared\NotFound;
use App\Tests\Support\ActsAsUser;
use App\Tests\Support\CreatesProducts;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class DesignUseCasesTest extends KernelTestCase
{
    use ActsAsUser;
    use CreatesProducts;
    private string $print;
    private string $sticker;

    protected function setUp(): void
    {
        self::actAsMemberOf();
        $types = self::getContainer()->get(CreateProductTypeHandler::class);
        $saveGabarit = self::getContainer()->get(SaveGabaritHandler::class);
        $this->print = (string) $saveGabarit(new SaveGabarit(null, 'Tirage 15×15', (string) $types('Print')->id(), 1_200, [], ['Recadrage carré']))->id();
        $this->sticker = (string) $saveGabarit(new SaveGabarit(null, 'Sticker brillant', (string) $types('Sticker')->id(), 400, ['5 cm', '8 cm']))->id();
    }

    public function testValidatingADesignCreatesOneProductPerDeclination(): void
    {
        $designId = $this->design('Forêt');
        $design = $this->view($designId);
        $print = $design->declinations[0];
        self::getContainer()->get(TickAdaptationHandler::class)($designId, $print['id'], 'Recadrage carré', true);
        self::getContainer()->get(AdjustDeclinationHandler::class)(new AdjustDeclination($designId, $design->declinations[1]['id'], 'Forêt', 450, ['8 cm']));

        self::assertSame(2, self::getContainer()->get(ValidateDesignHandler::class)($designId));
        $this->clear();

        $design = $this->view($designId);
        self::assertSame('validated', $design->status);
        self::assertNotNull($design->declinations[0]['productId']);
        $products = self::getContainer()->get(ListProductsHandler::class)();
        $sticker = array_values(array_filter($products, static fn (ProductView $product): bool => 'Sticker Forêt' === $product->displayName))[0];
        self::assertSame(450, $sticker->sellingPrice);
        self::assertSame(['8 cm'], $sticker->variants);
        self::assertSame('STI-FORET', $sticker->reference);
        self::assertContains('Print Forêt', array_map(static fn (ProductView $product): string => $product->displayName, $products));
    }

    public function testADesignWithPendingAdaptationsIsNotValidated(): void
    {
        $designId = $this->design('Forêt');

        try {
            self::getContainer()->get(ValidateDesignHandler::class)($designId);
            self::fail('Validation should have been refused.');
        } catch (InvalidDesign) {
        }
        $this->clear();

        self::assertSame('in_progress', $this->view($designId)->status);
        self::assertSame([], self::getContainer()->get(ListProductsHandler::class)());
    }

    public function testACollectionValidatesItsDesignsTogether(): void
    {
        $collectionId = (string) self::getContainer()->get(SaveCollectionHandler::class)(null, 'Sous-bois', 'Automne');
        $fern = (string) self::getContainer()->get(SaveDesignHandler::class)(new SaveDesign(null, 'Fougère', $collectionId, null, [$this->sticker]));
        $moss = (string) self::getContainer()->get(SaveDesignHandler::class)(new SaveDesign(null, 'Mousse', $collectionId, null, [$this->sticker]));

        self::assertSame(2, self::getContainer()->get(ValidateDesignHandler::class)->collection($collectionId));
        $this->clear();

        $board = self::getContainer()->get(ListDesignsHandler::class)();
        self::assertTrue($board->collections[0]['validated']);
        self::assertSame('validated', $this->view($fern)->status);
        self::assertSame('validated', $this->view($moss)->status);
    }

    public function testDesignsAndCollectionsCanBeMarkedAsWorkInProgress(): void
    {
        $collectionId = (string) self::getContainer()->get(SaveCollectionHandler::class)(null, 'Sous-bois', null);
        $designId = $this->design('Forêt');

        self::getContainer()->get(WorkOnHandler::class)->design($designId, false);
        self::getContainer()->get(WorkOnHandler::class)->collection($collectionId, false);
        $this->clear();

        $board = self::getContainer()->get(ListDesignsHandler::class)();
        self::assertFalse($board->standalone[0]->current);
        self::assertFalse($board->collections[0]['current']);
    }

    public function testAnExistingProductGetsItsDesignThenANewDeclination(): void
    {
        $productId = self::createProduct('Héron', 400, 60, ['5 cm']);
        $designProduct = self::getContainer()->get(DesignProductHandler::class);

        $designId = (string) $designProduct->create($productId, $this->sticker);
        $this->clear();

        self::assertSame(['id' => $designId, 'name' => 'Héron'], self::getContainer()->get(GetProductHandler::class)($productId)->design);
        self::assertSame('validated', $this->view($designId)->status);

        self::getContainer()->get(DeclineDesignHandler::class)($designId, $this->print);
        $print = $this->view($designId)->declinations[1];
        self::getContainer()->get(TickAdaptationHandler::class)($designId, $print['id'], 'Recadrage carré', true);
        self::assertSame(1, self::getContainer()->get(ValidateDesignHandler::class)($designId));
        $this->clear();

        self::assertContains('Print Héron', array_map(static fn (ProductView $product): string => $product->displayName, self::getContainer()->get(ListProductsHandler::class)()));
        $this->expectException(InvalidDesign::class);
        $designProduct->create($productId, $this->print);
    }

    public function testDesignsBelongToTheWorkspace(): void
    {
        $designId = $this->design('Forêt');

        self::actAsMemberOf('Autre atelier');

        $this->expectException(NotFound::class);
        $this->view($designId);
    }

    private function design(string $name): string
    {
        return (string) self::getContainer()->get(SaveDesignHandler::class)(new SaveDesign(null, $name, null, null, [$this->print, $this->sticker]));
    }

    private function view(string $designId): DesignView
    {
        return self::getContainer()->get(GetDesignHandler::class)($designId);
    }

    private function clear(): void
    {
        self::getContainer()->get('doctrine')->getManager()->clear();
    }
}
