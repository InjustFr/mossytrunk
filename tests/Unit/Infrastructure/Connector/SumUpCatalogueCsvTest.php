<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Connector;

use App\Application\Integration\CatalogueItem;
use App\Domain\Shared\Money;
use App\Infrastructure\Connector\SumUp\SumUpCatalogueCsv;
use PHPUnit\Framework\TestCase;

final class SumUpCatalogueCsvTest extends TestCase
{
    public function testFollowsSumUpImportTemplate(): void
    {
        $csv = (new SumUpCatalogueCsv())->of([
            new CatalogueItem('Sticker Mousse', 'Sticker', 'STK-001', Money::cents(400), []),
            new CatalogueItem('Print "Forêt", grand', 'Print', 'PRT-002', Money::cents(1_250), ['A4', 'A5']),
        ]);

        self::assertSame(
            "Item name,Variations,Price,Tax rate (%),Track inventory?,Quantity,SKU,Description,Category\r\n"
            ."Sticker Mousse,,4.00,,No,,STK-001,,Sticker\r\n"
            ."\"Print \"\"Forêt\"\", grand\",,,,,,,,Print\r\n"
            .",A4,12.50,,No,,PRT-002-A4,,\r\n"
            .",A5,12.50,,No,,PRT-002-A5,,\r\n",
            $csv,
        );
    }

    public function testAnEmptyCatalogueHasOnlyTheHeader(): void
    {
        self::assertSame("Item name,Variations,Price,Tax rate (%),Track inventory?,Quantity,SKU,Description,Category\r\n", (new SumUpCatalogueCsv())->of([]));
    }
}
