<?php

declare(strict_types=1);

namespace App\Tests\Functional\Application\Product;

use App\Application\Discount\CreateDiscountRule\CreateDiscountRuleHandler;
use App\Application\Discount\ListDiscountRules\ListDiscountRulesHandler;
use App\Application\Product\CreateProductType\CreateProductTypeHandler;
use App\Application\Product\DeleteProductType\DeleteProductTypeHandler;
use App\Application\Product\ListProductTypes\ListProductTypesHandler;
use App\Domain\Discount\Exception\OnlyEligibleType;
use App\Domain\Product\Exception\TypeStillUsed;
use App\Tests\Support\ActsAsUser;
use App\Tests\Support\CreatesProducts;
use App\Tests\Support\DiscountRules;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class DeleteProductTypeTest extends KernelTestCase
{
    use ActsAsUser;
    use CreatesProducts;

    protected function setUp(): void
    {
        self::actAsMemberOf();
    }

    public function testATypeUsedByAProductIsKept(): void
    {
        $sticker = $this->type('Sticker');
        self::createProduct('Mousse', 400, typeId: $sticker);

        $this->expectException(TypeStillUsed::class);
        self::getContainer()->get(DeleteProductTypeHandler::class)($sticker);
    }

    public function testDeletingATypeWithdrawsItFromDiscounts(): void
    {
        $print = $this->type('Print');
        $sticker = $this->type('Sticker');
        $mousse = self::createProduct('Mousse', 400, typeId: $sticker);
        self::getContainer()->get(CreateDiscountRuleHandler::class)(DiscountRules::fixedPrice('Print et sticker', 1_500, DiscountRules::type($print, 1), DiscountRules::product($mousse, 1)));

        self::getContainer()->get(DeleteProductTypeHandler::class)($print);

        self::assertSame(['Sticker'], array_column(self::getContainer()->get(ListProductTypesHandler::class)(), 'name'));
        $rules = self::getContainer()->get(ListDiscountRulesHandler::class)();
        self::assertSame(['Sticker Mousse'], array_column($rules[0]->conditions, 'name'));
    }

    public function testATypeThatIsTheOnlyConditionOfADiscountIsKept(): void
    {
        $print = $this->type('Print');
        self::getContainer()->get(CreateDiscountRuleHandler::class)(DiscountRules::fixedPrice('2 prints', 2_500, DiscountRules::type($print, 2)));

        $this->expectException(OnlyEligibleType::class);
        self::getContainer()->get(DeleteProductTypeHandler::class)($print);
    }

    private function type(string $name): string
    {
        return (string) self::getContainer()->get(CreateProductTypeHandler::class)($name)->id();
    }
}
