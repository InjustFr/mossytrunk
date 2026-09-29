<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure;

use App\Infrastructure\SumUp\SumUpPayloadMapper;
use PHPUnit\Framework\TestCase;

final class SumUpPayloadMapperTest extends TestCase
{
    public function testMapsAmountsAndOptionalCategory(): void
    {
        $transaction = (new SumUpPayloadMapper())->transaction([
            'transaction_code' => 'T1',
            'timestamp' => '2030-03-14T10:00:00Z',
            'amount' => 19.5,
            'tip_amount' => 0.5,
            'products' => [
                ['name' => 'Forêt', 'description' => "  A4 \n", 'price_label' => 'Prix', 'price' => 15.0, 'price_with_vat' => 15.0, 'quantity' => 1, 'category' => ['name' => 'Print']],
                ['name' => 'Mousse', 'price' => 4.0, 'quantity' => 1, 'category_name' => 'Sticker'],
                ['name' => 'Libre', 'price' => 0.5],
            ],
        ]);

        self::assertSame(1_900, $transaction->amountPaid->amount());
        self::assertSame(['Print', 'Sticker', null], array_map(static fn ($line) => $line->category, $transaction->lines));
        self::assertSame(['A4', null, null], array_map(static fn ($line) => $line->variant, $transaction->lines));
        self::assertSame(50, $transaction->lines[2]->unitPrice->amount());
    }
}
