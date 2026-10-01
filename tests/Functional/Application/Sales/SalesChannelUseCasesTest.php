<?php

declare(strict_types=1);

namespace App\Tests\Functional\Application\Sales;

use App\Application\Integration\CompleteAuthorization\CompleteAuthorizationHandler;
use App\Application\Integration\ConfigureConnection\AddConnectionHandler;
use App\Application\Integration\ExportCatalogue\ExportCatalogueHandler;
use App\Application\Integration\ImportSales\ImportSalesHandler;
use App\Application\Order\ListOrders\ListOrdersHandler;
use App\Application\Order\ListOrders\OrderSummaryView;
use App\Application\Product\BatchUpdateProducts\BatchUpdateProducts;
use App\Application\Product\BatchUpdateProducts\BatchUpdateProductsHandler;
use App\Application\Product\BatchUpdateProducts\ChannelPriceChange;
use App\Application\Product\BatchUpdateProducts\ChannelPriceMode;
use App\Application\Product\CreateProduct\CreateProduct;
use App\Application\Product\CreateProduct\CreateProductHandler;
use App\Application\Product\ListProducts\ListProductsHandler;
use App\Application\Product\ListProducts\ProductView;
use App\Application\Sales\DeleteChannel\DeleteChannelHandler;
use App\Application\Sales\ListChannels\ListChannelsHandler;
use App\Application\Sales\ListChannels\SalesChannelView;
use App\Application\Sales\SaveChannel\SaveChannelHandler;
use App\Domain\Integration\UnknownItems;
use App\Domain\Sales\ChannelKind;
use App\Domain\Sales\Exception\ChannelHasOrdersWithoutEvent;
use App\Domain\Sales\Exception\ChannelNameTaken;
use App\Domain\Sales\Exception\MainChannelKept;
use App\Domain\Sales\Exception\ServiceAlreadyLinked;
use App\Domain\Sales\Exception\UnknownSalesService;
use App\Domain\Shared\Exception\InvalidMoney;
use App\Domain\Shared\Exception\NotFound;
use App\Tests\Support\ActsAsUser;
use App\Tests\Support\CreatesProducts;
use App\Tests\Support\ExternalSales;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Translation\LocaleSwitcher;

final class SalesChannelUseCasesTest extends KernelTestCase
{
    use ActsAsUser;
    use CreatesProducts;

    protected function setUp(): void
    {
        self::getContainer()->get(LocaleSwitcher::class)->setLocale('en');
        self::actAsMemberOf();
    }

    public function testChannelsAreNamedOnceAndLinkedToOneServiceEach(): void
    {
        $etsy = $this->channel('Etsy', 'etsy');
        $this->channel('Marchés en ligne');
        self::getContainer()->get(SaveChannelHandler::class)($etsy, 'Boutique Etsy', ChannelKind::Online, 'etsy');

        self::assertEquals([
            new SalesChannelView($etsy, 'Boutique Etsy', 'etsy', 'Etsy', 'online', false),
        ], array_values(array_filter($this->channels(), static fn (SalesChannelView $view): bool => null !== $view->service)));
        self::assertSame(['Marchés', 'Boutique Etsy', 'Marchés en ligne'], array_column($this->channels(), 'name'));

        $this->expectExceptionObject(new ChannelNameTaken('marchés en ligne'));
        $this->channel('marchés en ligne');
    }

    public function testAServiceFeedsOneChannelOnly(): void
    {
        $this->channel('Etsy', 'etsy');

        $this->expectExceptionObject(new ServiceAlreadyLinked('Etsy', 'Etsy'));
        $this->channel('Autre boutique', 'etsy');
    }

    public function testOnlyAKnownServiceCanBeLinked(): void
    {
        $this->expectException(UnknownSalesService::class);

        $this->channel('Boutique', 'shopify');
    }

    public function testTheMainMarketChannelIsKept(): void
    {
        $main = $this->channels()[0];
        self::assertSame(['Marchés', 'market', true], [$main->name, $main->kind, $main->main]);

        $this->expectException(MainChannelKept::class);
        self::getContainer()->get(DeleteChannelHandler::class)($main->id);
    }

    public function testAChannelWithOrdersWithoutEventCannotBecomeAMarket(): void
    {
        $etsy = $this->channel('Etsy', 'etsy');
        ExternalSales::connect(self::getContainer()->get(AddConnectionHandler::class), 'etsy', ['keystring' => 'keystring123', 'shared_secret' => 'shared-secret'], unknownItems: UnknownItems::CreateProduct);
        self::getContainer()->get(CompleteAuthorizationHandler::class)('etsy', 'code', 'verifier', 'https://app.test/settings/etsy/callback');
        self::getContainer()->get(ImportSalesHandler::class)('etsy');
        self::getContainer()->get('doctrine')->getManager()->clear();

        self::assertNotSame([], array_filter(self::getContainer()->get(ListOrdersHandler::class)(), static fn (OrderSummaryView $order): bool => 'Etsy' === $order->channelName));

        $this->expectException(ChannelHasOrdersWithoutEvent::class);
        self::getContainer()->get(SaveChannelHandler::class)($etsy, 'Etsy', ChannelKind::Market, 'etsy');
    }

    public function testProductsGetTheirOwnPriceOnAChannelUntilItIsDeleted(): void
    {
        $etsy = $this->channel('Etsy', 'etsy');
        $id = (string) self::getContainer()->get(CreateProductHandler::class)(new CreateProduct('Forêt', 1_500, channelPrices: [$etsy => 1_800]));
        self::createProduct('Mousse', 400);

        self::assertSame([$etsy => 1_800], $this->view('Forêt')->channelPrices);
        self::assertSame([], $this->view('Mousse')->channelPrices);

        self::getContainer()->get(DeleteChannelHandler::class)($etsy);
        self::getContainer()->get('doctrine')->getManager()->clear();

        self::assertSame([], $this->view('Forêt')->channelPrices);
        self::assertSame($id, $this->view('Forêt')->id);
    }

    public function testBatchSetsAFixedPriceOnAChannel(): void
    {
        $etsy = $this->channel('Etsy');
        $mousse = self::createProduct('Mousse', 400);

        $this->batch([$mousse], new ChannelPriceChange($etsy, ChannelPriceMode::Fixed, 550));

        self::assertSame([$etsy => 550], $this->view('Mousse')->channelPrices);
        self::assertSame(400, $this->view('Mousse')->sellingPrice);
    }

    public function testBatchDerivesAChannelFromTheSellingPriceOrAnotherChannel(): void
    {
        $etsy = $this->channel('Etsy');
        $shop = $this->channel('Boutique');
        $foret = self::createProduct('Forêt', 1_500);
        $mousse = self::createProduct('Mousse', 400);

        $this->batch([$foret, $mousse], new ChannelPriceChange($etsy, ChannelPriceMode::Derived, adjustmentBasisPoints: 1_000));
        self::assertSame([1_650, 440], [$this->view('Forêt')->channelPrices[$etsy], $this->view('Mousse')->channelPrices[$etsy]]);

        $this->batch([$foret], new ChannelPriceChange($shop, ChannelPriceMode::Derived, sourceChannelId: $etsy, adjustmentCents: -150));
        self::assertSame(1_500, $this->view('Forêt')->channelPrices[$shop]);

        $this->batch([$mousse], new ChannelPriceChange(null, ChannelPriceMode::Derived, sourceChannelId: $etsy, adjustmentCents: 10));
        self::assertSame(450, $this->view('Mousse')->sellingPrice);
    }

    public function testBatchMakesAChannelFollowTheSellingPriceAgain(): void
    {
        $etsy = $this->channel('Etsy');
        $mousse = self::createProduct('Mousse', 400);
        $this->batch([$mousse], new ChannelPriceChange($etsy, ChannelPriceMode::Fixed, 550));

        $this->batch([$mousse], new ChannelPriceChange($etsy, ChannelPriceMode::SellingPrice));

        self::assertSame([], $this->view('Mousse')->channelPrices);
    }

    public function testAPriceBelowZeroAbortsTheBatch(): void
    {
        $etsy = $this->channel('Etsy');
        $mousse = self::createProduct('Mousse', 400);
        $foret = self::createProduct('Forêt', 1_500);

        try {
            $this->batch([$foret, $mousse], new ChannelPriceChange($etsy, ChannelPriceMode::Derived, adjustmentCents: -500));
            self::fail('Negative price accepted');
        } catch (InvalidMoney) {
        }

        self::getContainer()->get('doctrine')->getManager()->clear();
        self::assertSame([], $this->view('Forêt')->channelPrices);
    }

    public function testTheCatalogueExportedForAServiceCarriesItsChannelPrices(): void
    {
        $sumup = $this->channel('Stand', 'sumup');
        self::getContainer()->get(CreateProductHandler::class)(new CreateProduct('Forêt', 1_500, channelPrices: [$sumup => 1_200]));

        $file = self::getContainer()->get(ExportCatalogueHandler::class)('sumup');

        self::assertStringContainsString(',12.00,', $file->content);
        self::assertStringNotContainsString('15.00', $file->content);
    }

    public function testChannelsBelongToTheirWorkspace(): void
    {
        $etsy = $this->channel('Etsy');
        self::actAsMemberOf('Autre atelier');

        self::assertSame(['Marchés'], array_column($this->channels(), 'name'));
        $this->expectException(NotFound::class);
        self::getContainer()->get(DeleteChannelHandler::class)($etsy);
    }

    private function channel(string $name, ?string $service = null, ChannelKind $kind = ChannelKind::Online): string
    {
        return (string) self::getContainer()->get(SaveChannelHandler::class)(null, $name, $kind, $service);
    }

    /**
     * @return list<SalesChannelView>
     */
    private function channels(): array
    {
        return self::getContainer()->get(ListChannelsHandler::class)();
    }

    /**
     * @param list<string> $productIds
     */
    private function batch(array $productIds, ChannelPriceChange $change): void
    {
        self::getContainer()->get(BatchUpdateProductsHandler::class)(new BatchUpdateProducts($productIds, channelPrice: $change));
        self::getContainer()->get('doctrine')->getManager()->clear();
    }

    private function view(string $name): ProductView
    {
        return array_values(array_filter(self::getContainer()->get(ListProductsHandler::class)(), static fn (ProductView $view): bool => $view->name === $name))[0];
    }
}
