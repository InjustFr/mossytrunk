<?php

declare(strict_types=1);

namespace App\Tests\Functional\Application\Purchasing;

use App\Application\Purchasing\DeleteSupplierOrder\DeleteSupplierOrderHandler;
use App\Application\Purchasing\GetSupplierOrder\GetSupplierOrderHandler;
use App\Application\Purchasing\ListSupplierOrders\ListSupplierOrdersHandler;
use App\Application\Purchasing\ListSuppliers\ListSuppliersHandler;
use App\Application\Purchasing\PlaceSupplierOrder\PlaceSupplierOrderHandler;
use App\Application\Purchasing\PurchaseLine;
use App\Application\Purchasing\ReceiveSupplierOrder\ReceiveSupplierOrderHandler;
use App\Application\Purchasing\ReviseSupplierOrder\ReviseSupplierOrderHandler;
use App\Application\Purchasing\SaveSupplier\SaveSupplier;
use App\Application\Purchasing\SaveSupplier\SaveSupplierHandler;
use App\Application\Purchasing\SupplierOrderDraft;
use App\Application\Purchasing\SupplierOrderView;
use App\Application\Stock\GetProductStock\GetProductStockHandler;
use App\Domain\Purchasing\InvalidPurchase;
use App\Domain\Shared\NotFound;
use App\Tests\Support\ActsAsUser;
use App\Tests\Support\CreatesProducts;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class PurchasingUseCasesTest extends KernelTestCase
{
    use ActsAsUser;
    use CreatesProducts;
    private string $supplierId;
    private string $sticker;
    private string $tshirt;

    protected function setUp(): void
    {
        self::actAsMemberOf();
        $this->supplierId = (string) self::getContainer()->get(SaveSupplierHandler::class)(new SaveSupplier(null, 'Imprimerie du Lac'))->id();
        $this->sticker = (string) self::createProduct('Sticker', 400, 30);
        $this->tshirt = (string) self::createProduct('T-shirt', 2_000, 900, ['S', 'M']);
    }

    public function testReceivingAnOrderStocksWhatArrivedAtTheRealUnitCost(): void
    {
        $orderId = $this->place([new PurchaseLine($this->sticker, null, 100, 2_000), new PurchaseLine($this->tshirt, 'M', 10, 8_000)]);
        $lines = $this->view($orderId)->lines;

        self::getContainer()->get(ReceiveSupplierOrderHandler::class)($orderId, [$lines[0]['id'] => 125, $lines[1]['id'] => 8]);
        $this->clear();

        $view = $this->view($orderId);
        self::assertSame('received', $view->status);
        self::assertSame(16, $view->lines[0]['unitCost']);
        self::assertSame(1_000, $view->lines[1]['unitCost']);

        $stickerStock = self::getContainer()->get(GetProductStockHandler::class)($this->sticker)[0];
        self::assertSame(125, $stickerStock->onHand);
        self::assertSame('supplier_order', $stickerStock->lots[0]['origin']);
        self::assertSame($orderId, $stickerStock->lots[0]['sourceId']);
        $medium = self::getContainer()->get(GetProductStockHandler::class)($this->tshirt)[1];
        self::assertSame(8, $medium->onHand);
        self::assertSame(8_000, $medium->remainingValue);
    }

    public function testDiscountAndDeliveryFeesAreStockedWithTheLines(): void
    {
        $orderId = (string) self::getContainer()->get(PlaceSupplierOrderHandler::class)(new SupplierOrderDraft(
            $this->supplierId,
            new \DateTimeImmutable('2026-09-01'),
            [new PurchaseLine($this->sticker, null, 100, 2_000), new PurchaseLine($this->tshirt, 'S', 10, 2_000)],
            400,
            600,
        ));
        $lines = $this->view($orderId)->lines;
        self::assertSame(4_200, $this->view($orderId)->total);

        self::getContainer()->get(ReceiveSupplierOrderHandler::class)($orderId, [$lines[0]['id'] => 100, $lines[1]['id'] => 10]);
        $this->clear();

        $sticker = self::getContainer()->get(GetProductStockHandler::class)($this->sticker)[0];
        self::assertSame(2_000 - 200 + 300, $sticker->remainingValue);
    }

    public function testAReceivedOrderCanNeitherBeRevisedNorDeleted(): void
    {
        $orderId = $this->place([new PurchaseLine($this->sticker, null, 10, 300)]);
        self::getContainer()->get(ReceiveSupplierOrderHandler::class)($orderId, [$this->view($orderId)->lines[0]['id'] => 10]);

        $this->expectException(InvalidPurchase::class);

        self::getContainer()->get(DeleteSupplierOrderHandler::class)($orderId);
    }

    public function testAnOrderedOrderCanBeRevisedThenDeleted(): void
    {
        $orderId = $this->place([new PurchaseLine($this->sticker, null, 10, 300)]);

        self::getContainer()->get(ReviseSupplierOrderHandler::class)($orderId, new SupplierOrderDraft($this->supplierId, new \DateTimeImmutable('2026-09-02'), [new PurchaseLine($this->tshirt, 'S', 4, 3_600)]));
        $this->clear();

        $view = $this->view($orderId);
        self::assertSame('2026-09-02', $view->orderedOn);
        self::assertSame(['T-shirt — S'], array_column($view->lines, 'label'));

        self::getContainer()->get(DeleteSupplierOrderHandler::class)($orderId);
        self::assertSame([], self::getContainer()->get(ListSupplierOrdersHandler::class)());
    }

    public function testSupplierNamesAreUniquePerWorkspace(): void
    {
        $this->expectException(InvalidPurchase::class);

        self::getContainer()->get(SaveSupplierHandler::class)(new SaveSupplier(null, 'imprimerie du lac'));
    }

    public function testSuppliersAndOrdersBelongToTheWorkspace(): void
    {
        $orderId = $this->place([new PurchaseLine($this->sticker, null, 10, 300)]);

        self::actAsMemberOf('Autre atelier');

        self::assertSame([], self::getContainer()->get(ListSuppliersHandler::class)());
        $this->expectException(NotFound::class);
        $this->view($orderId);
    }

    /**
     * @param list<PurchaseLine> $lines
     */
    private function place(array $lines): string
    {
        return (string) self::getContainer()->get(PlaceSupplierOrderHandler::class)(new SupplierOrderDraft($this->supplierId, new \DateTimeImmutable('2026-09-01'), $lines));
    }

    private function view(string $orderId): SupplierOrderView
    {
        return self::getContainer()->get(GetSupplierOrderHandler::class)($orderId);
    }

    private function clear(): void
    {
        self::getContainer()->get('doctrine')->getManager()->clear();
    }
}
