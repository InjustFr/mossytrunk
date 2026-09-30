<?php

declare(strict_types=1);

namespace App\Tests\Functional\Application\Product;

use App\Application\Design\AdjustDeclination\AdjustDeclination;
use App\Application\Design\AdjustDeclination\AdjustDeclinationHandler;
use App\Application\Design\GetDesign\GetDesignHandler;
use App\Application\Design\ListGabarits\ListGabaritsHandler;
use App\Application\Design\SaveDesign\SaveDesign;
use App\Application\Design\SaveDesign\SaveDesignHandler;
use App\Application\Design\SaveGabarit\SaveGabarit;
use App\Application\Design\SaveGabarit\SaveGabaritHandler;
use App\Application\Discount\CreateDiscountRule\CreateDiscountRuleHandler;
use App\Application\Discount\ListDiscountRules\ListDiscountRulesHandler;
use App\Application\Event\ScheduleEvent\ScheduleEvent;
use App\Application\Event\ScheduleEvent\ScheduleEventHandler;
use App\Application\Order\GetOrder\GetOrderHandler;
use App\Application\Order\PlaceOrder\PlaceOrder;
use App\Application\Order\PlaceOrder\PlaceOrderHandler;
use App\Application\Order\RequestedLine;
use App\Application\Product\CreateProductType\CreateProductTypeHandler;
use App\Application\Product\ListProducts\ListProductsHandler;
use App\Application\Product\ListProductTypes\ListProductTypesHandler;
use App\Application\Product\UpdateProductType\UpdateProductTypeHandler;
use App\Application\Product\Variants\RenameTypeVariantHandler;
use App\Application\Purchasing\GetSupplierOrder\GetSupplierOrderHandler;
use App\Application\Purchasing\PlaceSupplierOrder\PlaceSupplierOrderHandler;
use App\Application\Purchasing\PurchaseLine;
use App\Application\Purchasing\SaveSupplier\SaveSupplier;
use App\Application\Purchasing\SaveSupplier\SaveSupplierHandler;
use App\Application\Purchasing\SupplierOrderDraft;
use App\Application\Stock\GetProductStock\GetProductStockHandler;
use App\Application\Stock\Restock\Restock;
use App\Application\Stock\Restock\RestockHandler;
use App\Domain\Product\Exception\DuplicateTypeVariant;
use App\Domain\Product\Exception\VariantInUse;
use App\Tests\Support\ActsAsUser;
use App\Tests\Support\CreatesProducts;
use App\Tests\Support\DiscountRules;
use App\Tests\Support\DomainExceptions;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class TypeVariantsTest extends KernelTestCase
{
    use ActsAsUser;
    use CreatesProducts;

    private string $print;

    protected function setUp(): void
    {
        self::actAsMemberOf();
        self::getContainer()->get(ScheduleEventHandler::class)(new ScheduleEvent('Salon', 'Lyon', new \DateTimeImmutable('2030-03-14'), new \DateTimeImmutable('2030-03-15')));
        $this->print = (string) self::getContainer()->get(CreateProductTypeHandler::class)('Print', variants: ['A4', 'A3'])->id();
    }

    public function testATypeIsCreatedWithItsVariantsAndHowItNamesProducts(): void
    {
        self::getContainer()->get(CreateProductTypeHandler::class)('Original', variants: ['Encadré', 'Nu'], prefixesNames: false);

        $types = array_column(self::getContainer()->get(ListProductTypesHandler::class)(), null, 'name');

        self::assertSame([['Encadré', 'Nu'], false], [$types['Original']->variants, $types['Original']->prefixesNames]);
        self::assertSame([['A4', 'A3'], true], [$types['Print']->variants, $types['Print']->prefixesNames]);
    }

    public function testRenamingAVariantRenamesItEverywhereItIsUsedByTheType(): void
    {
        $forest = self::createProduct('Forêt', 1_500, 300, ['A4', 'A3'], $this->print);
        $shirt = self::createProduct('T-shirt', 2_000, 800, ['A4'], (string) self::getContainer()->get(CreateProductTypeHandler::class)('T-shirt')->id());
        $gabarit = (string) self::getContainer()->get(SaveGabaritHandler::class)(new SaveGabarit(null, 'Tirage', $this->print, 1_500, ['A4', 'A3']))->id();
        $design = (string) self::getContainer()->get(SaveDesignHandler::class)(new SaveDesign(null, 'Rivière', null, null, [$gabarit]));
        self::getContainer()->get(CreateDiscountRuleHandler::class)(DiscountRules::fixedPrice(
            'A4',
            2_500,
            DiscountRules::type($this->print, 1, 'A4'),
            DiscountRules::product($forest, 1, 'A4'),
        ));
        self::getContainer()->get(RestockHandler::class)(new Restock($forest, 'A4', 5, 1_500));
        self::getContainer()->get(RestockHandler::class)(new Restock($shirt, 'A4', 2, 1_600));
        $order = (string) self::getContainer()->get(PlaceOrderHandler::class)(new PlaceOrder(new \DateTimeImmutable('2030-03-14 12:00'), [new RequestedLine($forest, 'A4', 1)]))->id();
        $supplier = (string) self::getContainer()->get(SaveSupplierHandler::class)(new SaveSupplier(null, 'Imprimerie du Lac'))->id();
        $supplierOrder = (string) self::getContainer()->get(PlaceSupplierOrderHandler::class)(new SupplierOrderDraft($supplier, new \DateTimeImmutable('2030-03-01'), [new PurchaseLine($forest, 'A4', 10, 2_000)]));

        self::getContainer()->get(RenameTypeVariantHandler::class)($this->print, 'a4', ' Grand ');
        self::getContainer()->get('doctrine')->getManager()->clear();

        $types = array_column(self::getContainer()->get(ListProductTypesHandler::class)(), 'variants', 'name');
        self::assertSame(['Grand', 'A3'], $types['Print']);
        self::assertSame(['A4'], $types['T-shirt'], 'another type keeps its variant');
        $products = array_column(self::getContainer()->get(ListProductsHandler::class)(), 'variants', 'name');
        self::assertSame(['Grand', 'A3'], $products['Forêt']);
        self::assertSame(['A4'], $products['T-shirt']);
        self::assertSame(['Grand', 'A3'], self::getContainer()->get(ListGabaritsHandler::class)()[0]->variants);
        $declination = self::getContainer()->get(GetDesignHandler::class)($design)->declinations[0];
        self::assertSame(['Grand', 'A3'], $declination['variants']);
        self::assertSame(['id' => $gabarit, 'name' => 'Tirage', 'typeId' => $this->print, 'typeName' => 'Print', 'prefixesNames' => true], $declination['gabarit']);
        self::assertSame(['Print · Grand', 'Print Forêt · Grand'], array_column(self::getContainer()->get(ListDiscountRulesHandler::class)()[0]->conditions, 'name'));
        self::assertSame(['Grand' => 4, 'A3' => 0], self::stock($forest));
        self::assertSame(['A4' => 2], self::stock($shirt));
        self::assertSame(['Print Forêt — Grand'], array_column(self::getContainer()->get(GetOrderHandler::class)($order)->lines, 'label'));
        self::assertSame(['Grand'], array_column(self::getContainer()->get(GetSupplierOrderHandler::class)($supplierOrder)->lines, 'variant'));
    }

    public function testARenamedVariantKeepsSellingWithItsDiscount(): void
    {
        $forest = self::createProduct('Forêt', 1_500, 300, ['A4', 'A3'], $this->print);
        self::getContainer()->get(CreateDiscountRuleHandler::class)(DiscountRules::fixedPrice('A4 à 10 €', 1_000, DiscountRules::type($this->print, 1, 'A4')));

        self::getContainer()->get(RenameTypeVariantHandler::class)($this->print, 'A4', 'Grand');
        self::getContainer()->get('doctrine')->getManager()->clear();
        $order = (string) self::getContainer()->get(PlaceOrderHandler::class)(new PlaceOrder(new \DateTimeImmutable('2030-03-14 12:00'), [new RequestedLine($forest, 'grand', 1), new RequestedLine($forest, 'A3', 1)]))->id();

        self::assertSame(2_500, self::getContainer()->get(GetOrderHandler::class)($order)->total);
    }

    public function testAVariantCannotBeRenamedAsAnotherOneOfTheType(): void
    {
        self::createProduct('Forêt', 1_500, 300, ['A4', 'A3'], $this->print);

        DomainExceptions::assertThrown(new DuplicateTypeVariant('a3'), fn () => self::getContainer()->get(RenameTypeVariantHandler::class)($this->print, 'A4', 'a3'));
        self::getContainer()->get('doctrine')->getManager()->clear();

        self::assertSame(['A4', 'A3'], self::getContainer()->get(ListProductTypesHandler::class)()[0]->variants);
        self::assertSame(['A4', 'A3'], self::getContainer()->get(ListProductsHandler::class)()[0]->variants);
    }

    public function testVariantsAreReorderedAndUnusedOnesDropped(): void
    {
        self::createProduct('Forêt', 1_500, 300, ['A4'], $this->print);
        $update = self::getContainer()->get(UpdateProductTypeHandler::class);

        $update($this->print, 'Print', '#5b7f3a', variants: ['A5', 'A4', 'A3']);
        $update($this->print, 'Print', '#5b7f3a', variants: ['A4', 'A5'], prefixesNames: false);
        self::getContainer()->get('doctrine')->getManager()->clear();

        $type = self::getContainer()->get(ListProductTypesHandler::class)()[0];
        self::assertSame([['A4', 'A5'], false], [$type->variants, $type->prefixesNames]);
        self::assertSame('Forêt', self::getContainer()->get(ListProductsHandler::class)()[0]->displayName);
    }

    public function testAVariantStillUsedCannotBeDropped(): void
    {
        $card = (string) self::getContainer()->get(CreateProductTypeHandler::class)('Carte', variants: ['A6', 'A5', 'Carré', 'Rond', 'A4'])->id();
        $gabarit = (string) self::getContainer()->get(SaveGabaritHandler::class)(new SaveGabarit(null, 'Carte postale', $card, 250, ['A6']))->id();
        $design = (string) self::getContainer()->get(SaveDesignHandler::class)(new SaveDesign(null, 'Rivière', null, null, [$gabarit]));
        $declination = self::getContainer()->get(GetDesignHandler::class)($design)->declinations[0]['id'];
        self::getContainer()->get(AdjustDeclinationHandler::class)(new AdjustDeclination($design, $declination, 'Rivière', 250, ['A5']));
        self::getContainer()->get(CreateDiscountRuleHandler::class)(DiscountRules::fixedPrice('Cartes carrées', 1_000, DiscountRules::type($card, 2, 'Carré')));
        self::createProduct('Forêt', 250, 50, ['A4'], $card);
        $update = self::getContainer()->get(UpdateProductTypeHandler::class);

        DomainExceptions::assertThrown(new VariantInUse('A6'), static fn () => $update($card, 'Carte', '#5b7f3a', variants: ['A5', 'Carré', 'Rond', 'A4']));
        DomainExceptions::assertThrown(new VariantInUse('A5'), static fn () => $update($card, 'Carte', '#5b7f3a', variants: ['A6', 'Carré', 'Rond', 'A4']));
        DomainExceptions::assertThrown(new VariantInUse('Carré'), static fn () => $update($card, 'Carte', '#5b7f3a', variants: ['A6', 'A5', 'Rond', 'A4']));
        DomainExceptions::assertThrown(new VariantInUse('A4'), static fn () => $update($card, 'Carte', '#5b7f3a', variants: ['A6', 'A5', 'Carré', 'Rond']));

        $update($card, 'Carte', '#5b7f3a', variants: ['a4', 'Carré', 'A5', 'A6']);
        self::getContainer()->get('doctrine')->getManager()->clear();

        self::assertSame(['a4', 'Carré', 'A5', 'A6'], array_column(self::getContainer()->get(ListProductTypesHandler::class)(), 'variants', 'name')['Carte']);
    }

    /**
     * @return array<string, int>
     */
    private static function stock(string $productId): array
    {
        $stock = [];
        foreach (self::getContainer()->get(GetProductStockHandler::class)($productId) as $item) {
            $stock[(string) $item->variant] = $item->onHand;
        }

        return $stock;
    }
}
