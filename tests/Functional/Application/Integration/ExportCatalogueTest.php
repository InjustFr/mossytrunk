<?php

declare(strict_types=1);

namespace App\Tests\Functional\Application\Integration;

use App\Application\Integration\Exception\ServiceNotExportingCatalogue;
use App\Application\Integration\ExportCatalogue\ExportCatalogueHandler;
use App\Application\Product\ArchiveProduct\ArchiveProductHandler;
use App\Application\Product\CreateProductType\CreateProductTypeHandler;
use App\Domain\Product\ProductRepository;
use App\Tests\Support\ActsAsUser;
use App\Tests\Support\CreatesProducts;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Uid\Ulid;

final class ExportCatalogueTest extends KernelTestCase
{
    use ActsAsUser;
    use CreatesProducts;

    protected function setUp(): void
    {
        self::actAsMemberOf();
    }

    public function testExportsTheActiveCatalogueWithTheNamesTheSalesImportRecognises(): void
    {
        $print = (string) self::getContainer()->get(CreateProductTypeHandler::class)('Print', variants: ['A4', 'A5', 'A3'])->id();
        $forest = self::createProduct('Forêt', 1_500, variants: ['A4', 'A5', 'A3'], typeId: $print);
        self::getContainer()->get(ProductRepository::class)->get(Ulid::fromString($forest))->type()->archiveVariants(['A3']);
        self::getContainer()->get(EntityManagerInterface::class)->flush();
        $archived = self::createProduct('Ancien', 900, typeId: (string) self::getContainer()->get(CreateProductTypeHandler::class)('Sticker')->id());
        self::getContainer()->get(ArchiveProductHandler::class)($archived);

        $file = self::getContainer()->get(ExportCatalogueHandler::class)('sumup');

        self::assertSame(1, $file->itemCount);
        self::assertMatchesRegularExpression('/^catalogue-sumup-\d{4}-\d{2}-\d{2}\.csv$/', $file->filename);
        self::assertStringContainsString("Print Forêt,,,,,,,,Print\r\n,A4,15.00,", $file->content);
        self::assertStringContainsString(',A5,15.00,', $file->content);
        self::assertStringNotContainsString('A3', $file->content);
        self::assertStringNotContainsString('Ancien', $file->content);
    }

    public function testOnlyAServiceThatExportsCataloguesCanBeAsked(): void
    {
        $this->expectException(ServiceNotExportingCatalogue::class);

        self::getContainer()->get(ExportCatalogueHandler::class)('etsy');
    }
}
