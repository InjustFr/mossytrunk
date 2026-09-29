<?php

declare(strict_types=1);

namespace App\Tests\Functional\Application\Etsy;

use App\Application\Accounting\ExportOrders\ExportOrdersHandler;
use App\Application\Etsy\ConnectEtsy\ConnectEtsyHandler;
use App\Application\Etsy\DisconnectEtsy\DisconnectEtsyHandler;
use App\Application\Etsy\EtsySession;
use App\Application\Etsy\EtsyUnavailable;
use App\Application\Etsy\ImportFromEtsy\ImportFromEtsyHandler;
use App\Application\Etsy\LinkEtsyListing\LinkEtsyListingHandler;
use App\Application\Etsy\ListEtsyListings\ListEtsyListingsHandler;
use App\Application\Etsy\UpdateEtsySettings\UpdateEtsySettingsHandler;
use App\Application\Order\GetOrder\GetOrderHandler;
use App\Application\Order\ListOrders\ListOrdersHandler;
use App\Application\Product\CreateProductType\CreateProductTypeHandler;
use App\Application\Stock\GetProductStock\GetProductStockHandler;
use App\Application\Workspace\GetSettings\GetWorkspaceSettingsHandler;
use App\Application\WorkspaceContext;
use App\Domain\Identity\WorkspaceRepository;
use App\Tests\Support\ActsAsUser;
use App\Tests\Support\CreatesProducts;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class EtsyUseCasesTest extends KernelTestCase
{
    use ActsAsUser;
    use CreatesProducts;
    private string $sticker;
    private string $print;

    protected function setUp(): void
    {
        self::actAsMemberOf();
        $types = self::getContainer()->get(CreateProductTypeHandler::class);
        $this->sticker = self::createProduct('Mousse', 400, 60, typeId: (string) $types('Sticker')->id());
        $this->print = self::createProduct('Forêt', 1_800, 300, ['A4', 'A3'], (string) $types('Print')->id());
        self::getContainer()->get(UpdateEtsySettingsHandler::class)('keystring123', 'shared-secret');
    }

    public function testConnectingStoresTheShop(): void
    {
        self::assertSame('Atelier Mousse sur Etsy', self::getContainer()->get(ConnectEtsyHandler::class)->complete('code', 'verifier', 'https://app.test/parametres/etsy/retour'));

        $etsy = self::getContainer()->get(GetWorkspaceSettingsHandler::class)()->etsy;
        self::assertTrue($etsy->connected);
        self::assertSame('Atelier Mousse sur Etsy', $etsy->shopName);

        self::getContainer()->get(DisconnectEtsyHandler::class)();
        self::assertFalse(self::getContainer()->get(GetWorkspaceSettingsHandler::class)()->etsy->connected);
    }

    public function testTheAppKeysBelongToTheWorkspaceAndChangingThemDisconnectsTheShop(): void
    {
        self::getContainer()->get(ConnectEtsyHandler::class)->complete('code', 'verifier', 'https://app.test/parametres/etsy/retour');
        $etsy = self::getContainer()->get(GetWorkspaceSettingsHandler::class)()->etsy;
        self::assertSame(['keystring123', true, '••••cret', true], [$etsy->keystring, $etsy->sharedSecretConfigured, $etsy->sharedSecretHint, $etsy->connected]);

        self::getContainer()->get(UpdateEtsySettingsHandler::class)('keystring123', null);
        self::assertTrue(self::getContainer()->get(GetWorkspaceSettingsHandler::class)()->etsy->connected);

        self::getContainer()->get(UpdateEtsySettingsHandler::class)('otherapp456', null);
        self::assertFalse(self::getContainer()->get(GetWorkspaceSettingsHandler::class)()->etsy->connected);

        self::actAsMemberOf('Autre atelier');
        $this->expectException(EtsyUnavailable::class);
        self::getContainer()->get(ConnectEtsyHandler::class)->authorizationUrl('https://app.test/retour', 'state', 'challenge');
    }

    public function testImportIsRefusedUntilAShopIsConnected(): void
    {
        $this->expectException(EtsyUnavailable::class);

        self::getContainer()->get(ImportFromEtsyHandler::class)();
    }

    public function testReceiptsWithAnUnknownListingWaitUntilItIsLinked(): void
    {
        self::getContainer()->get(ConnectEtsyHandler::class)->complete('code', 'verifier', 'https://app.test/parametres/etsy/retour');

        $first = self::getContainer()->get(ImportFromEtsyHandler::class)();
        self::assertSame([1, 0, 1, 1], [$first->ordersImported, $first->ordersAlreadyImported, $first->ordersWaitingForListings, $first->listingsToLink]);

        $listing = self::getContainer()->get(ListEtsyListingsHandler::class)()[0];
        self::assertSame(['A4', null], [$listing->variation, $listing->linkedTo]);
        self::getContainer()->get(LinkEtsyListingHandler::class)($listing->id, $this->print, 'A4');

        $second = self::getContainer()->get(ImportFromEtsyHandler::class)();
        self::assertSame([1, 1, 0, 0], [$second->ordersImported, $second->ordersAlreadyImported, $second->ordersWaitingForListings, $second->listingsToLink]);
        self::getContainer()->get('doctrine')->getManager()->clear();

        $orders = self::getContainer()->get(ListOrdersHandler::class)();
        $receipt = array_values(array_filter($orders, static fn ($order): bool => 'ETSY-3100000001' === $order->reference))[0];
        self::assertSame(['etsy', null], [$receipt->source, $receipt->eventName]);
        $order = self::getContainer()->get(GetOrderHandler::class)($receipt->id);
        self::assertSame([2_600, 100, 250, 2_750], [$order->subtotal, $order->discountTotal, $order->shipping, $order->total]);
        self::assertSame('Remise Etsy', $order->discounts[0]['label']);
        self::assertNull($order->event);
        self::assertSame(-3, self::getContainer()->get(GetProductStockHandler::class)($this->sticker)[0]->onHand);

        $csv = self::getContainer()->get(ExportOrdersHandler::class)('2030-01-01', '2030-01-02')->content;
        self::assertStringContainsString(';Etsy;;Carte;3;', $csv);
        self::assertStringContainsString(';26,00;1,00;2,50;27,50;', $csv);
    }

    public function testAnExpiredTokenIsRenewed(): void
    {
        self::getContainer()->get(ConnectEtsyHandler::class)->complete('code', 'verifier', 'https://app.test/parametres/etsy/retour');
        $workspace = self::getContainer()->get(WorkspaceRepository::class)->get(self::getContainer()->get(WorkspaceContext::class)->current()->id());
        $workspace->renewEtsyToken(new \DateTimeImmutable('-1 minute'));

        self::assertSame('fake-access-renewed', self::getContainer()->get(EtsySession::class)->accessToken($workspace));
        self::assertGreaterThan(new \DateTimeImmutable('+30 minutes'), $workspace->etsyTokenExpiresAt());
    }
}
