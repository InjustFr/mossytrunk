<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure;

use App\Infrastructure\Etsy\EtsyPayloadMapper;
use PHPUnit\Framework\TestCase;

final class EtsyPayloadMapperTest extends TestCase
{
    public function testAReceiptBecomesLinesWithVariationsDiscountAndShipping(): void
    {
        $receipt = (new EtsyPayloadMapper())->receipt([
            'receipt_id' => 42,
            'create_timestamp' => 1_893_456_000,
            'discount_amt' => ['amount' => 150, 'divisor' => 100],
            'total_shipping_cost' => ['amount' => 4_90, 'divisor' => 100],
            'transactions' => [[
                'listing_id' => 7,
                'title' => ' T-shirt Lichen ',
                'sku' => 'TSH-LICHEN',
                'quantity' => 2,
                'price' => ['amount' => 2_500, 'divisor' => 100],
                'variations' => [['formatted_name' => 'Taille', 'formatted_value' => 'M'], ['formatted_name' => 'Couleur', 'formatted_value' => 'Vert']],
            ]],
        ]);

        self::assertSame('42', $receipt->receiptId);
        self::assertSame('2030-01-01T00:00:00+00:00', $receipt->createdAt->format(\DATE_ATOM));
        self::assertSame([150, 490], [$receipt->discount->amount(), $receipt->shipping->amount()]);
        $line = $receipt->lines[0];
        self::assertSame(['7', 'T-shirt Lichen', 'TSH-LICHEN', 'M / Vert', 2, 2_500], [$line->listingId, $line->title, $line->sku, $line->variation, $line->quantity, $line->unitPrice->amount()]);
    }
}
