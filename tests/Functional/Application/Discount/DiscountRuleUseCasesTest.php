<?php

declare(strict_types=1);

namespace App\Tests\Functional\Application\Discount;

use App\Application\Discount\CreateDiscountRule\CreateDiscountRuleHandler;
use App\Application\Discount\DeleteDiscountRule\DeleteDiscountRuleHandler;
use App\Application\Discount\DiscountRuleDefinition;
use App\Application\Discount\ListDiscountRules\ListDiscountRulesHandler;
use App\Application\Discount\ToggleDiscountRule\ToggleDiscountRuleHandler;
use App\Application\Discount\UpdateDiscountRule\UpdateDiscountRuleHandler;
use App\Application\Product\CreateProduct\CreateProduct;
use App\Application\Product\CreateProduct\CreateProductHandler;
use App\Application\Product\CreateProductType\CreateProductTypeHandler;
use App\Application\Order\PreviewOrder\PreviewOrderHandler;
use App\Application\Order\RequestedLine;
use App\Application\Event\ScheduleEvent\ScheduleEvent;
use App\Application\Event\ScheduleEvent\ScheduleEventHandler;
use App\Domain\Shared\NotFound;
use App\Tests\Support\ActsAsUser;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class DiscountRuleUseCasesTest extends KernelTestCase
{
    use ActsAsUser;

    protected function setUp(): void
    {
        self::actAsMemberOf();
    }
    public function testDiscountRuleLifecycle(): void
    {
        $sticker = (string) $this->product('STK', 'Sticker', 400);
        $bigSticker = (string) $this->product('STK-XL', 'Sticker XL', 600);

        $id = (string) self::getContainer()->get(CreateDiscountRuleHandler::class)(new DiscountRuleDefinition('3 stickers pour 10 €', [$sticker], 3, 1_000));
        self::getContainer()->get(UpdateDiscountRuleHandler::class)($id, new DiscountRuleDefinition('3 stickers pour 12 €', [$sticker, $bigSticker], 3, 1_200));
        self::getContainer()->get(ToggleDiscountRuleHandler::class)($id, false);
        self::getContainer()->get('doctrine')->getManager()->clear();

        $rule = self::getContainer()->get(ListDiscountRulesHandler::class)()[0];
        self::assertSame('3 stickers pour 12 €', $rule->name);
        self::assertSame(1_200, $rule->bundlePrice);
        self::assertFalse($rule->active);
        self::assertEqualsCanonicalizing(['Sticker', 'Sticker XL'], array_column($rule->products, 'name'));

        self::getContainer()->get(DeleteDiscountRuleHandler::class)($id);
        self::assertSame([], self::getContainer()->get(ListDiscountRulesHandler::class)());
    }

    public function testRuleOnTypesAppliesToOrders(): void
    {
        $print = (string) self::getContainer()->get(CreateProductTypeHandler::class)('Print')->id();
        $sticker = (string) self::getContainer()->get(CreateProductTypeHandler::class)('Sticker')->id();
        $create = self::getContainer()->get(CreateProductHandler::class);
        $foret = (string) $create(new CreateProduct('Forêt', 1_500, typeId: $print));
        $mousse = (string) $create(new CreateProduct('Mousse', 400, typeId: $sticker));
        self::getContainer()->get(CreateDiscountRuleHandler::class)(new DiscountRuleDefinition('3 articles pour 30 €', [], 3, 3_000, [$print, $sticker]));
        self::getContainer()->get(ScheduleEventHandler::class)(new ScheduleEvent('Salon', 'Lyon', new \DateTimeImmutable('2026-07-09'), new \DateTimeImmutable('2026-07-09')));

        $rule = self::getContainer()->get(ListDiscountRulesHandler::class)()[0];
        self::assertEqualsCanonicalizing(['Print', 'Sticker'], array_column($rule->types, 'name'));

        $preview = self::getContainer()->get(PreviewOrderHandler::class)(new \DateTimeImmutable('2026-07-09 12:00'), [new RequestedLine($foret, null, 2), new RequestedLine($mousse, null, 1)]);
        self::assertSame(3_000, $preview->total);
    }

    public function testUnknownProductIsRejected(): void
    {
        $this->expectException(NotFound::class);

        self::getContainer()->get(CreateDiscountRuleHandler::class)(new DiscountRuleDefinition('Lot', ['01K00000000000000000000000'], 3, 1_000));
    }

    private function product(string $reference, string $name, int $price): \Symfony\Component\Uid\Ulid
    {
        return self::getContainer()->get(CreateProductHandler::class)(new CreateProduct($name, $price));
    }
}
