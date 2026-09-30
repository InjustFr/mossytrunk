<?php

declare(strict_types=1);

namespace App\Tests\Functional\Application\Product;

use App\Application\Discount\CreateDiscountRule\CreateDiscountRuleHandler;
use App\Application\Discount\ListDiscountRules\ListDiscountRulesHandler;
use App\Application\Product\DeleteProducts\DeleteProducts;
use App\Application\Product\DeleteProducts\DeleteProductsHandler;
use App\Application\Product\ListProducts\ListProductsHandler;
use App\Domain\Discount\Exception\OnlyEligibleProduct;
use App\Domain\Shared\Exception\NotFound;
use App\Tests\Support\ActsAsUser;
use App\Tests\Support\CreatesProducts;
use App\Tests\Support\DiscountRules;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class DeleteProductsTest extends KernelTestCase
{
    use ActsAsUser;
    use CreatesProducts;

    protected function setUp(): void
    {
        self::actAsMemberOf();
    }

    public function testTheSelectedProductsLeaveTheCatalogueAndTheirDiscountConditions(): void
    {
        $container = self::getContainer();
        $sticker = $this->product('Sticker');
        $pin = $this->product('Pin');
        $badge = $this->product('Badge');
        $container->get(CreateDiscountRuleHandler::class)(DiscountRules::fixedPrice('3 pour 10', 1_000, DiscountRules::product($sticker, 2), DiscountRules::product($pin, 1), DiscountRules::product($badge, 1)));

        self::assertSame(2, $this->delete([$pin, $badge, $pin]));

        self::assertSame(['Sticker'], array_column($container->get(ListProductsHandler::class)(), 'name'));
        self::assertSame(['Sticker'], DiscountRules::names($container->get(ListDiscountRulesHandler::class)()[0]));
    }

    public function testAProductThatADiscountTargetsAloneAbortsTheDeletion(): void
    {
        $sticker = $this->product('Sticker');
        $pin = $this->product('Pin');
        self::getContainer()->get(CreateDiscountRuleHandler::class)(DiscountRules::fixedPrice('2 pins', 700, DiscountRules::product($pin, 2)));

        try {
            $this->delete([$sticker, $pin]);
            self::fail('a product a discount targets alone was deleted');
        } catch (OnlyEligibleProduct) {
        }

        self::getContainer()->get('doctrine')->getManager()->clear();
        self::assertCount(2, self::getContainer()->get(ListProductsHandler::class)());
    }

    public function testAProductOfAnotherWorkspaceAbortsTheDeletion(): void
    {
        self::actAsMemberOf('Atelier B');
        $other = $this->product('Pin');
        self::actAsMemberOf('Atelier A');
        $mine = $this->product('Sticker');

        try {
            $this->delete([$mine, $other]);
            self::fail('a product of another workspace was deleted');
        } catch (NotFound) {
        }

        self::getContainer()->get('doctrine')->getManager()->clear();
        self::assertCount(1, self::getContainer()->get(ListProductsHandler::class)());
    }

    /**
     * @param list<string> $productIds
     */
    private function delete(array $productIds): int
    {
        $deleted = self::getContainer()->get(DeleteProductsHandler::class)(new DeleteProducts($productIds));
        self::getContainer()->get('doctrine')->getManager()->clear();

        return $deleted;
    }

    private function product(string $name): string
    {
        return (string) self::createProduct($name, 400);
    }
}
