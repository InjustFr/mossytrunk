<?php

declare(strict_types=1);

namespace App\Tests\Functional\Application\Discount;

use App\Application\Discount\ConditionDefinition;
use App\Application\Discount\CreateDiscountRule\CreateDiscountRuleHandler;
use App\Application\Discount\DeleteDiscountRule\DeleteDiscountRuleHandler;
use App\Application\Discount\DiscountRuleDefinition;
use App\Application\Discount\ListDiscountRules\ListDiscountRulesHandler;
use App\Application\Discount\ToggleDiscountRule\ToggleDiscountRuleHandler;
use App\Application\Discount\UpdateDiscountRule\UpdateDiscountRuleHandler;
use App\Application\Event\ScheduleEvent\ScheduleEvent;
use App\Application\Event\ScheduleEvent\ScheduleEventHandler;
use App\Application\Order\PreviewOrder\PreviewOrderHandler;
use App\Application\Order\RequestedLine;
use App\Application\Product\CreateProduct\CreateProduct;
use App\Application\Product\CreateProduct\CreateProductHandler;
use App\Application\Product\CreateProductType\CreateProductTypeHandler;
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
        $create = self::getContainer()->get(CreateProductHandler::class);
        $sticker = (string) $create(new CreateProduct('Sticker', 400));
        $bigSticker = (string) $create(new CreateProduct('Sticker XL', 600));

        $id = (string) self::getContainer()->get(CreateDiscountRuleHandler::class)(new DiscountRuleDefinition(
            '3 stickers pour 10 €',
            [new ConditionDefinition(ConditionDefinition::PRODUCT, $sticker, 3)],
            'fixedPrice',
            1_000,
        ));
        self::getContainer()->get(UpdateDiscountRuleHandler::class)($id, new DiscountRuleDefinition(
            'Sticker et sticker XL : −10 %',
            [new ConditionDefinition(ConditionDefinition::PRODUCT, $sticker, 1), new ConditionDefinition(ConditionDefinition::PRODUCT, $bigSticker, 1)],
            'percentOff',
            1_000,
            '2026-07-01',
            '2026-07-31',
        ));
        self::getContainer()->get(ToggleDiscountRuleHandler::class)($id, false);
        self::getContainer()->get('doctrine')->getManager()->clear();

        $rule = self::getContainer()->get(ListDiscountRulesHandler::class)()[0];
        self::assertSame('Sticker et sticker XL : −10 %', $rule->name);
        self::assertSame(['kind' => 'percentOff', 'value' => 1_000], $rule->action);
        self::assertSame(['2026-07-01', '2026-07-31'], [$rule->startsOn, $rule->endsOn]);
        self::assertFalse($rule->active);
        self::assertSame([['Sticker', 1], ['Sticker XL', 1]], array_map(static fn (array $c): array => [$c['name'], $c['quantity']], $rule->conditions));

        self::getContainer()->get(DeleteDiscountRuleHandler::class)($id);
        self::assertSame([], self::getContainer()->get(ListDiscountRulesHandler::class)());
    }

    public function testTwoPrintsAndOneStickerForFifteenEurosDuringItsPeriod(): void
    {
        $print = (string) self::getContainer()->get(CreateProductTypeHandler::class)('Print')->id();
        $sticker = (string) self::getContainer()->get(CreateProductTypeHandler::class)('Sticker')->id();
        $create = self::getContainer()->get(CreateProductHandler::class);
        $foret = (string) $create(new CreateProduct('Forêt', 1_500, typeId: $print));
        $mousse = (string) $create(new CreateProduct('Mousse', 400, typeId: $sticker));
        self::getContainer()->get(CreateDiscountRuleHandler::class)(new DiscountRuleDefinition(
            '2 prints et 1 sticker pour 15 €',
            [new ConditionDefinition(ConditionDefinition::TYPE, $print, 2), new ConditionDefinition(ConditionDefinition::TYPE, $sticker, 1)],
            'fixedPrice',
            1_500,
            '2026-07-09',
            '2026-07-09',
        ));
        self::getContainer()->get(ScheduleEventHandler::class)(new ScheduleEvent('Salon', 'Lyon', new \DateTimeImmutable('2026-07-09'), new \DateTimeImmutable('2026-07-10')));
        $lines = [new RequestedLine($foret, null, 2), new RequestedLine($mousse, null, 1)];

        $during = self::getContainer()->get(PreviewOrderHandler::class)(new \DateTimeImmutable('2026-07-09 12:00'), $lines);
        $after = self::getContainer()->get(PreviewOrderHandler::class)(new \DateTimeImmutable('2026-07-10 12:00'), $lines);

        self::assertSame(1_500, $during->total);
        self::assertSame('2 prints et 1 sticker pour 15 €', $during->discounts[0]['label']);
        self::assertNotNull($during->discounts[0]['ruleId']);
        self::assertSame(3_400, $after->total);
    }

    public function testUnknownProductIsRejected(): void
    {
        $this->expectException(NotFound::class);

        self::getContainer()->get(CreateDiscountRuleHandler::class)(new DiscountRuleDefinition(
            'Lot',
            [new ConditionDefinition(ConditionDefinition::PRODUCT, '01K00000000000000000000000', 3)],
            'fixedPrice',
            1_000,
        ));
    }
}
