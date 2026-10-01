<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Connector;

use App\Infrastructure\Connector\Etsy\EtsyInventorySkus;
use App\Tests\Support\EtsyListings;
use App\Tests\Support\Json;
use PHPUnit\Framework\TestCase;

final class EtsyInventorySkusTest extends TestCase
{
    public function testWritesSkusPerVariationAndSendsBackEverythingElseAsItWas(): void
    {
        $listing = EtsyListings::listing(2, 'Affiche', [['sku' => '', 'values' => ['A4']], ['sku' => '', 'values' => ['A5'], 'price' => 1_200, 'enabled' => false]]);

        $inventory = (new EtsyInventorySkus())->withSkus($listing['inventory'], ['A4' => 'PRT-002-A4', 'A5' => 'PRT-002-A5']);

        self::assertSame([
            'products' => [
                [
                    'sku' => 'PRT-002-A4',
                    'property_values' => [['property_id' => 513, 'value_ids' => [crc32('A4')], 'property_name' => 'Format', 'values' => ['A4']]],
                    'offerings' => [['price' => 18.0, 'quantity' => 5, 'is_enabled' => true, 'readiness_state_id' => 77]],
                ],
                [
                    'sku' => 'PRT-002-A5',
                    'property_values' => [['property_id' => 513, 'value_ids' => [crc32('A5')], 'property_name' => 'Format', 'values' => ['A5']]],
                    'offerings' => [['price' => 12.0, 'quantity' => 5, 'is_enabled' => false, 'readiness_state_id' => 77]],
                ],
            ],
            'price_on_property' => [513],
            'quantity_on_property' => [],
            'sku_on_property' => [513],
            'readiness_state_on_property' => [],
        ], $inventory);
    }

    public function testNothingIsSentWhenTheSkusAreAlreadyRight(): void
    {
        $listing = EtsyListings::listing(2, 'Affiche', [['sku' => 'PRT-002-A4', 'values' => ['A4']]]);

        self::assertNull((new EtsyInventorySkus())->withSkus($listing['inventory'], ['A4' => 'PRT-002-A4']));
    }

    public function testAListingWithoutVariationsGetsOneSkuAndKeepsUnlinkedVariationsAsTheyAre(): void
    {
        $single = (new EtsyInventorySkus())->withSkus(EtsyListings::listing(1, 'Sticker', [['sku' => '', 'values' => []]])['inventory'], ['' => 'STK-001']);
        $partial = (new EtsyInventorySkus())->withSkus(EtsyListings::listing(2, 'Affiche', [['sku' => 'MINE', 'values' => ['A4']], ['sku' => '', 'values' => ['A5']]])['inventory'], ['A5' => 'PRT-002-A5']);

        self::assertSame(['STK-001', []], [Json::string($single, 'products', 0, 'sku'), Json::at($single, 'sku_on_property')]);
        self::assertSame(['MINE', 'PRT-002-A5'], [Json::string($partial, 'products', 0, 'sku'), Json::string($partial, 'products', 1, 'sku')]);
    }
}
