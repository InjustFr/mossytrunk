<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Identity;

use App\Domain\Identity\InvalidAccount;
use App\Domain\Identity\SecretName;
use PHPUnit\Framework\TestCase;

final class SecretNameTest extends TestCase
{
    public function testAServiceFieldIsNamedServiceUnderscoreField(): void
    {
        self::assertSame('sumup_api_key', SecretName::of('sumup', 'api_key')->value);
        self::assertSame('etsy_refresh_token', SecretName::of('etsy', 'refresh_token')->value);
    }

    public function testANameIsLowercaseAndShort(): void
    {
        $this->expectException(InvalidAccount::class);

        SecretName::of('SumUp', str_repeat('x', 60));
    }
}
