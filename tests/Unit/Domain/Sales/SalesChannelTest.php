<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Sales;

use App\Domain\Sales\Exception\EmptyChannelName;
use App\Domain\Sales\SalesChannel;
use App\Tests\Support\TestWorkspace;
use PHPUnit\Framework\TestCase;

final class SalesChannelTest extends TestCase
{
    public function testNameIsTrimmedAndServiceIsOptional(): void
    {
        $channel = SalesChannel::open(TestWorkspace::get(), '  Boutique Etsy ', service: ' ');

        self::assertSame('Boutique Etsy', $channel->name());
        self::assertNull($channel->service());
        self::assertTrue($channel->isNamed('boutique etsy'));

        $channel->linkTo('etsy');
        self::assertSame('etsy', $channel->service());
    }

    public function testNameIsRequired(): void
    {
        $this->expectException(EmptyChannelName::class);

        SalesChannel::open(TestWorkspace::get(), '   ');
    }
}
