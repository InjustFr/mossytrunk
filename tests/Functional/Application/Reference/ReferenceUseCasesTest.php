<?php

declare(strict_types=1);

namespace App\Tests\Functional\Application\Reference;

use App\Application\Event\ScheduleEvent\ScheduleEvent;
use App\Application\Event\ScheduleEvent\ScheduleEventHandler;
use App\Application\Order\DeleteOrder\DeleteOrderHandler;
use App\Application\Order\PlaceOrder\PlaceOrder;
use App\Application\Order\PlaceOrder\PlaceOrderHandler;
use App\Application\Order\RequestedLine;
use App\Application\Product\CreateProductType\CreateProductTypeHandler;
use App\Application\Product\ListProducts\ListProductsHandler;
use App\Application\Product\SuggestProductReference\SuggestProductReferenceHandler;
use App\Application\Purchasing\PlaceSupplierOrder\PlaceSupplierOrderHandler;
use App\Application\Purchasing\PurchaseLine;
use App\Application\Purchasing\SaveSupplier\SaveSupplier;
use App\Application\Purchasing\SaveSupplier\SaveSupplierHandler;
use App\Application\Purchasing\SupplierOrderDraft;
use App\Application\Reference\ChangeReferenceFormat\ChangeReferenceFormat;
use App\Application\Reference\ChangeReferenceFormat\ChangeReferenceFormatHandler;
use App\Application\Reference\ListReferenceFormats\ListReferenceFormatsHandler;
use App\Application\Reference\PreviewReferenceFormat\PreviewReferenceFormatHandler;
use App\Domain\Order\Order;
use App\Domain\Order\OrderRepository;
use App\Domain\Purchasing\SupplierOrderRepository;
use App\Domain\Reference\Exception\UnknownReferenceToken;
use App\Domain\Reference\ReferenceKind;
use App\Tests\Support\ActsAsUser;
use App\Tests\Support\CreatesProducts;
use App\Tests\Support\DomainExceptions;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Uid\Ulid;

final class ReferenceUseCasesTest extends KernelTestCase
{
    use ActsAsUser;
    use CreatesProducts;
    private string $sticker;

    protected function setUp(): void
    {
        self::actAsMemberOf();
        self::getContainer()->get(ScheduleEventHandler::class)(
            new ScheduleEvent('Japan Expo', 'Villepinte', new \DateTimeImmutable('2026-07-09'), new \DateTimeImmutable('2026-07-12')),
        );
        $this->sticker = self::createProduct('Sticker', 400);
    }

    public function testEachKindListsItsFormatTagsExampleAndExistingItems(): void
    {
        $this->place('2026-07-10 15:00');

        $formats = array_column(self::getContainer()->get(ListReferenceFormatsHandler::class)(), null, 'kind');

        self::assertSame(['order', 'supplier_order', 'product'], array_keys($formats));
        $order = $formats['order'];
        self::assertSame(['CMD-{date}-{random}', 'CMD-{date}-{random}', 1], [$order->template, $order->defaultTemplate, $order->existing]);
        self::assertContains('time', $order->tokens);
        self::assertMatchesRegularExpression('/^CMD-\d{8}-[0-9A-Z]{6}$/', $order->example);
        $product = $formats['product'];
        self::assertSame(['PRI-FOR', 1], [$product->example, $product->existing]);
        self::assertSame(['type', 'name', 'date', 'year', 'month', 'day', 'number', 'random'], $product->tokens);
    }

    public function testANewFormatAppliesToFutureCreationsOnly(): void
    {
        $before = $this->place('2026-07-10 15:00');

        $renamed = $this->change(ReferenceKind::Order, 'V{year}-{number:4}', applyToExisting: false);
        $first = $this->place('2026-07-11 10:00');
        $second = $this->place('2026-07-09 10:00');

        self::assertSame(0, $renamed);
        self::assertSame(['V2026-0001', 'V2026-0002'], [$first->reference(), $second->reference()]);
        self::assertMatchesRegularExpression('/^CMD-20260710-/', $this->orders()->get($before->id())->reference());
    }

    public function testApplyingToExistingItemsRenumbersThemOldestFirst(): void
    {
        $late = $this->place('2026-07-11 10:00');
        $early = $this->place('2026-07-09 10:00');
        $middle = $this->place('2026-07-10 10:00');

        self::assertSame(3, $this->change(ReferenceKind::Order, 'C-{date}-{number:3}', applyToExisting: true));
        self::assertSame(['C-20260709-001', 'C-20260710-002', 'C-20260711-003'], $this->referencesOf($early, $middle, $late));

        self::getContainer()->get(DeleteOrderHandler::class)((string) $early->id());
        self::assertSame(2, $this->change(ReferenceKind::Order, 'C-{date}-{number:3}', applyToExisting: true), 'references shift down onto the ones the others still held');
        self::assertSame(['C-20260710-001', 'C-20260711-002'], $this->referencesOf($middle, $late));
        self::assertSame('C-20260712-003', $this->place('2026-07-12 10:00')->reference());
    }

    public function testSupplierOrdersAndProductsFollowTheirOwnFormat(): void
    {
        $supplierId = (string) self::getContainer()->get(SaveSupplierHandler::class)(new SaveSupplier(null, 'Imprimerie du Lac'))->id();
        $this->change(ReferenceKind::SupplierOrder, 'ACHAT/{year:2}{month}/{number:2}', applyToExisting: false);
        $orderId = self::getContainer()->get(PlaceSupplierOrderHandler::class)(new SupplierOrderDraft($supplierId, new \DateTimeImmutable('2026-09-01'), [new PurchaseLine($this->sticker, null, 10, 1_000)], 0, 0));
        self::assertSame('ACHAT/2609/01', self::getContainer()->get(SupplierOrderRepository::class)->get($orderId)->reference());

        $print = (string) self::getContainer()->get(CreateProductTypeHandler::class)('Print')->id();
        self::createProduct('Forêt', 1_500, typeId: $print);
        self::assertSame('PRI-FOR-2', self::getContainer()->get(SuggestProductReferenceHandler::class)(Ulid::fromString($print), 'Forêt'));

        $this->change(ReferenceKind::Product, 'P{number:3}-{name}', applyToExisting: true);
        self::assertEqualsCanonicalizing(['P001-STI', 'P002-FOR'], array_column(self::getContainer()->get(ListProductsHandler::class)(), 'reference'));
        self::assertSame('P003-RIV', self::getContainer()->get(SuggestProductReferenceHandler::class)(null, 'Rivière'));
    }

    public function testThePreviewShowsTheNextReferenceAndRejectsAnInvalidTemplate(): void
    {
        $preview = self::getContainer()->get(PreviewReferenceFormatHandler::class);

        self::assertSame('PRI-FOR-0001', $preview(ReferenceKind::Product, '{type}-{name}-{number:4}'));
        DomainExceptions::assertThrown(new UnknownReferenceToken('name'), static fn () => $preview(ReferenceKind::Order, '{name}'));
    }

    public function testFormatsBelongToTheirWorkspace(): void
    {
        $this->change(ReferenceKind::Order, 'A{number}', applyToExisting: false);
        self::assertSame('A1', $this->place('2026-07-10 15:00')->reference());

        self::actAsMemberOf('Autre atelier');
        $formats = array_column(self::getContainer()->get(ListReferenceFormatsHandler::class)(), null, 'kind');
        $order = $formats['order'];
        self::assertSame(['CMD-{date}-{random}', 0], [$order->template, $order->existing]);
    }

    private function change(ReferenceKind $kind, string $template, bool $applyToExisting): int
    {
        $renamed = self::getContainer()->get(ChangeReferenceFormatHandler::class)(new ChangeReferenceFormat($kind, $template, $applyToExisting));
        self::getContainer()->get(EntityManagerInterface::class)->clear();

        return $renamed;
    }

    private function place(string $localTime): Order
    {
        return self::getContainer()->get(PlaceOrderHandler::class)(
            new PlaceOrder(new \DateTimeImmutable($localTime, new \DateTimeZone('Europe/Paris')), [new RequestedLine($this->sticker, null, 1)]),
        );
    }

    /**
     * @return list<string>
     */
    private function referencesOf(Order ...$orders): array
    {
        return array_map(fn (Order $order): string => $this->orders()->get($order->id())->reference(), array_values($orders));
    }

    private function orders(): OrderRepository
    {
        return self::getContainer()->get(OrderRepository::class);
    }
}
