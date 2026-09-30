<?php

declare(strict_types=1);

namespace App\Tests\Functional\Application\Product;

use App\Application\Product\BatchUpdateProducts\BatchUpdateProducts;
use App\Application\Product\BatchUpdateProducts\BatchUpdateProductsHandler;
use App\Application\Product\CreateProductType\CreateProductTypeHandler;
use App\Application\Product\ListProducts\ListProductsHandler;
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

    public function testAddAndRemoveVariantsOnAllPrints(): void
    {
        $foret = $this->product('Forêt', 1_500, ['A5', 'A4']);
        $riviere = $this->product('Rivière', 1_500, ['A4']);

        $this->batch(new BatchUpdateProducts([$foret, $riviere], addVariants: ['A3', 'A4'], removeVariants: ['A5']));

        $variants = array_column(self::getContainer()->get(ListProductsHandler::class)(), 'variants', 'name');
        self::assertSame(['A4', 'A3'], $variants['Forêt']);
        self::assertSame(['A4', 'A3'], $variants['Rivière']);
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

    private function batch(BatchUpdateProducts $command): int
    {
        $updated = self::getContainer()->get(BatchUpdateProductsHandler::class)($command);
        self::getContainer()->get('doctrine')->getManager()->clear();

        return $updated;
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
