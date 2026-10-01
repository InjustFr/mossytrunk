<?php

declare(strict_types=1);

namespace App\Tests\Functional\Application\Product;

use App\Application\Product\BatchUpdateProducts\BatchUpdateProducts;
use App\Application\Product\BatchUpdateProducts\BatchUpdateProductsHandler;
use App\Application\Product\CreateProductType\CreateProductTypeHandler;
use App\Application\Product\GetProduct\GetProductHandler;
use App\Application\Product\ListProducts\ListProductsHandler;
use App\Application\Product\ListProducts\ProductView;
use App\Application\Stock\Restock\Restock;
use App\Application\Stock\Restock\RestockHandler;
use App\Domain\Product\Exception\PriceDatedInTheFuture;
use App\Domain\Product\Exception\VariantChoiceMissing;
use App\Domain\Shared\Exception\InvalidMoney;
use App\Tests\Support\ActsAsUser;
use App\Tests\Support\CreatesProducts;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class BatchUpdateProductsTest extends KernelTestCase
{
    use ActsAsUser;
    use CreatesProducts;

    protected function setUp(): void
    {
        self::actAsMemberOf();
    }

    public function testRepriceAllStickers(): void
    {
        $sticker = (string) self::getContainer()->get(CreateProductTypeHandler::class)('Sticker')->id();
        $mousse = $this->product('Mousse', 400, typeId: $sticker);
        $fougere = $this->product('Fougère', 450, typeId: $sticker);
        $this->product('Forêt', 1_500);

        $updated = $this->batch(new BatchUpdateProducts([$mousse, $fougere], sellingPriceCents: 500));

        self::assertSame(2, $updated);
        self::assertSame(['Forêt' => 1_500, 'Sticker Fougère' => 500, 'Sticker Mousse' => 500], $this->prices());
    }

    public function testSetTheLowStockAlertOfSeveralProducts(): void
    {
        $mousse = $this->product('Mousse', 400);
        $fougere = $this->product('Fougère', 450);
        $this->product('Forêt', 1_500);

        $this->batch(new BatchUpdateProducts([$mousse, $fougere], lowStockThreshold: 3));

        self::assertSame(['Forêt' => 10, 'Fougère' => 3, 'Mousse' => 3], $this->thresholds());
    }

    public function testABatchPriceCanStartOnAPastDay(): void
    {
        $mousse = $this->product('Mousse', 400);
        $since = (new \DateTimeImmutable('-3 days', new \DateTimeZone('Europe/Paris')))->format('Y-m-d');

        $this->batch(new BatchUpdateProducts([$mousse], sellingPriceCents: 450, priceSinceDay: $since));

        $history = self::getContainer()->get(GetProductHandler::class)($mousse)->priceHistory;
        self::assertSame([[400, null], [450, $since]], array_map(static fn (array $change): array => [$change['price'], $change['sinceDay'] === $since ? $since : null], $history));

        $this->batch(new BatchUpdateProducts([$mousse], sellingPriceCents: 500, priceSinceDay: (new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris')))->format('Y-m-d')));
        self::assertSame(['Mousse' => 500], $this->prices());

        $this->expectException(PriceDatedInTheFuture::class);
        $this->batch(new BatchUpdateProducts([$mousse], sellingPriceCents: 600, priceSinceDay: (new \DateTimeImmutable('+2 days'))->format('Y-m-d')));
    }

    public function testAddAndRemoveVariantsOnAllPrints(): void
    {
        $foret = $this->product('Forêt', 1_500, ['A5', 'A4']);
        $riviere = $this->product('Rivière', 1_500, ['A4']);

        $this->batch(new BatchUpdateProducts([$foret, $riviere], addVariants: ['A3', 'A4'], removeVariants: ['A5']));

        $variants = array_column(self::getContainer()->get(ListProductsHandler::class)(), 'variants', 'name');
        self::assertSame(['A4', 'A3'], $variants['Forêt']);
        self::assertSame(['A4', 'A3'], $variants['Rivière']);
    }

    public function testKeepsTheStockOfEveryVariantStillSold(): void
    {
        $sticker = $this->product('Mousse', 400, ['A5']);
        $print = $this->product('Forêt', 1_500, ['A5', 'A4']);
        $this->restock($sticker, 'A5', 12);
        $this->restock($print, 'A5', 7);
        $this->restock($print, 'A4', 3);

        $this->batch(new BatchUpdateProducts([$sticker, $print], sellingPriceCents: 900));

        self::assertSame([12, 10], [$this->onHand('Mousse'), $this->onHand('Forêt')]);
    }

    public function testAUniqueProductMovedToATypeWithVariantsKeepsItsStock(): void
    {
        $print = (string) self::getContainer()->get(CreateProductTypeHandler::class)('Print', variants: ['A5', 'A4'])->id();
        $zine = $this->product('Zine', 1_000);
        $this->restock($zine, null, 138);

        $this->batch(new BatchUpdateProducts([$zine], changeType: true, typeId: $print, addVariants: ['A4']));

        self::assertSame(138, $this->onHand('Zine'));
    }

    public function testChangeType(): void
    {
        $print = (string) self::getContainer()->get(CreateProductTypeHandler::class)('Print')->id();
        $foret = $this->product('Forêt', 1_500);

        $this->batch(new BatchUpdateProducts([$foret], changeType: true, typeId: $print));

        $product = self::getContainer()->get(ListProductsHandler::class)()[0];
        self::assertSame('Print Forêt', $product->displayName);
        self::assertSame(1_500, $product->sellingPrice);
    }

    public function testInvalidChangeAbortsTheWholeBatch(): void
    {
        $foret = $this->product('Forêt', 1_500);

        try {
            $this->batch(new BatchUpdateProducts([$foret], sellingPriceCents: -1));
            self::fail('Negative price accepted');
        } catch (InvalidMoney) {
        }

        self::assertSame(['Forêt' => 1_500], $this->prices());
    }

    /**
     * @param list<string> $variants
     */
    private function product(string $name, int $price, array $variants = [], ?string $typeId = null): string
    {
        return (string) self::createProduct($name, $price, 0, $variants, $typeId);
    }

    public function testMovingProductsToATypeWithVariantsNeedsAVariant(): void
    {
        $print = (string) self::getContainer()->get(CreateProductTypeHandler::class)('Print', variants: ['A5', 'A4'])->id();
        $zine = $this->product('Zine', 1_000);

        try {
            $this->batch(new BatchUpdateProducts([$zine], changeType: true, typeId: $print));
            self::fail('a product of a type with variants needs one of them');
        } catch (VariantChoiceMissing) {
        }

        $this->batch(new BatchUpdateProducts([$zine], changeType: true, typeId: $print, addVariants: ['A5']));
        self::assertSame(['A5'], self::getContainer()->get(ListProductsHandler::class)()[0]->variants);
    }

    private function restock(string $productId, ?string $variant, int $quantity): void
    {
        self::getContainer()->get(RestockHandler::class)(new Restock($productId, $variant, $quantity, $quantity * 100));
        self::getContainer()->get('doctrine')->getManager()->clear();
    }

    private function onHand(string $name): int
    {
        $product = array_values(array_filter(self::getContainer()->get(ListProductsHandler::class)(), static fn (ProductView $view): bool => $view->name === $name))[0];

        return $product->onHand;
    }

    private function batch(BatchUpdateProducts $command): int
    {
        $updated = self::getContainer()->get(BatchUpdateProductsHandler::class)($command);
        self::getContainer()->get('doctrine')->getManager()->clear();

        return $updated;
    }

    /**
     * @return array<string, int>
     */
    private function thresholds(): array
    {
        $thresholds = array_column(self::getContainer()->get(ListProductsHandler::class)(), 'lowStockThreshold', 'name');
        ksort($thresholds);

        return $thresholds;
    }

    /**
     * @return array<string, int>
     */
    private function prices(): array
    {
        self::getContainer()->get('doctrine')->getManager()->clear();
        $prices = [];
        foreach (self::getContainer()->get(ListProductsHandler::class)() as $product) {
            $prices[$product->displayName] = $product->sellingPrice;
        }
        ksort($prices);

        return $prices;
    }
}
