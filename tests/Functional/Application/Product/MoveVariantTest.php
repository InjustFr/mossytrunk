<?php

declare(strict_types=1);

namespace App\Tests\Functional\Application\Product;

use App\Application\Discount\CreateDiscountRule\CreateDiscountRuleHandler;
use App\Application\Discount\ListDiscountRules\ListDiscountRulesHandler;
use App\Application\Event\ScheduleEvent\ScheduleEvent;
use App\Application\Event\ScheduleEvent\ScheduleEventHandler;
use App\Application\Order\GetOrder\GetOrderHandler;
use App\Application\Order\PlaceOrder\PlaceOrder;
use App\Application\Order\PlaceOrder\PlaceOrderHandler;
use App\Application\Order\RequestedLine;
use App\Application\Product\ListProducts\ListProductsHandler;
use App\Application\Product\MoveVariant\MoveVariant;
use App\Application\Product\MoveVariant\MoveVariantHandler;
use App\Domain\Product\Exception\SoldWithoutVariant;
use App\Domain\Product\Exception\UnknownVariant;
use App\Domain\Product\Exception\VariantMovedOntoItself;
use App\Tests\Support\ActsAsUser;
use App\Tests\Support\CreatesProducts;
use App\Tests\Support\DiscountRules;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class MoveVariantTest extends KernelTestCase
{
    use ActsAsUser;
    use CreatesProducts;

    protected function setUp(): void
    {
        self::actAsMemberOf();
        self::getContainer()->get(ScheduleEventHandler::class)(new ScheduleEvent('Salon', 'Lyon', new \DateTimeImmutable('2030-03-14'), new \DateTimeImmutable('2030-03-15')));
    }

    public function testProductsSplitPerVariantBecomeOneProductWithVariants(): void
    {
        $lichen = $this->product('Mug Lichen', 1_200);
        $fougere = $this->product('Mug Fougère', 1_200);
        $order = $this->order([[$lichen, null, 2], [$fougere, null, 1]]);

        $mug = (string) $this->move(new MoveVariant($lichen, null, null, 'Mug', 'Lichen'));
        $this->move(new MoveVariant($fougere, null, $mug, null, 'Fougère'));

        $products = array_column($this->products(), null, 'name');
        self::assertSame(['Mug'], array_keys($products));
        self::assertSame(['Lichen', 'Fougère'], $products['Mug']->variants);
        self::assertSame(1_200, $products['Mug']->sellingPrice);
        $lines = self::getContainer()->get(GetOrderHandler::class)($order)->lines;
        self::assertEqualsCanonicalizing([['Mug — Lichen', 2], ['Mug — Fougère', 1]], array_map(static fn (array $line): array => [$line['label'], $line['quantity']], $lines));
        self::assertSame(3_600, self::getContainer()->get(GetOrderHandler::class)($order)->total, 'prices of past sales are kept');
    }

    public function testAVariantMovesToAnotherProductAndMergesWithItsSales(): void
    {
        $shirt = $this->product('T-shirt', 2_000, ['S', 'M']);
        $tee = $this->product('Tee', 2_000, ['M']);
        $order = $this->order([[$shirt, 'M', 1], [$tee, 'M', 2], [$shirt, 'S', 1]]);

        $this->move(new MoveVariant($shirt, 'M', $tee, null, 'M'));

        $variants = array_column($this->products(), 'variants', 'name');
        self::assertSame(['S'], $variants['T-shirt']);
        self::assertSame(['M'], $variants['Tee']);
        $lines = self::getContainer()->get(GetOrderHandler::class)($order)->lines;
        self::assertEqualsCanonicalizing([['Tee — M', 3], ['T-shirt — S', 1]], array_map(static fn (array $line): array => [$line['label'], $line['quantity']], $lines));
    }

    public function testMovingTheLastVariantRemovesTheProductAndMovesItsDiscounts(): void
    {
        $old = $this->product('Vieux print', 1_500, ['A4']);
        $print = $this->product('Print', 1_500, ['A3']);
        self::getContainer()->get(CreateDiscountRuleHandler::class)(DiscountRules::fixedPrice('2 prints', 2_500, DiscountRules::product($old, 2)));

        $this->move(new MoveVariant($old, 'A4', $print, null, 'A4'));

        self::assertSame(['Print'], array_column($this->products(), 'name'));
        self::assertSame(['Print'], array_column(self::getContainer()->get(ListDiscountRulesHandler::class)()[0]->conditions, 'name'));
    }

    public function testATargetSoldWithoutVariantCannotGetAFirstOne(): void
    {
        $lichen = $this->product('Mug Lichen', 1_200);
        $mug = $this->product('Mug', 1_200);
        $this->order([[$mug, null, 1]]);

        $this->expectExceptionObject(new SoldWithoutVariant('Mug'));
        $this->move(new MoveVariant($lichen, null, $mug, null, 'Lichen'));
    }

    public function testAProductCannotMoveOntoItself(): void
    {
        $shirt = $this->product('T-shirt', 2_000, ['S', 'M']);

        $this->expectExceptionObject(new VariantMovedOntoItself());
        $this->move(new MoveVariant($shirt, 'M', $shirt, null, 'L'));
    }

    public function testTheMovedVariantMustExist(): void
    {
        $shirt = $this->product('T-shirt', 2_000, ['S']);

        $this->expectExceptionObject(new UnknownVariant('T-shirt', 'XL'));
        $this->move(new MoveVariant($shirt, 'XL', null, 'Tee', null));
    }

    /**
     * @param list<string> $variants
     */
    private function product(string $name, int $price, array $variants = []): string
    {
        return (string) self::createProduct($name, $price, 300, $variants);
    }

    /**
     * @param list<array{string, ?string, int}> $lines
     */
    private function order(array $lines): string
    {
        $order = self::getContainer()->get(PlaceOrderHandler::class)(new PlaceOrder(
            new \DateTimeImmutable('2030-03-14 12:00', new \DateTimeZone('Europe/Paris')),
            array_map(static fn (array $line): RequestedLine => new RequestedLine(...$line), $lines),
        ));

        return (string) $order->id();
    }

    private function move(MoveVariant $command): \Symfony\Component\Uid\Ulid
    {
        $target = self::getContainer()->get(MoveVariantHandler::class)($command);
        self::getContainer()->get('doctrine')->getManager()->clear();

        return $target;
    }

    /**
     * @return list<\App\Application\Product\ListProducts\ProductView>
     */
    private function products(): array
    {
        return self::getContainer()->get(ListProductsHandler::class)();
    }
}
