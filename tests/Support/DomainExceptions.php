<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Domain\Shared\Exception\DomainException;
use PHPUnit\Framework\Assert;

final class DomainExceptions
{
    public static function assertThrown(DomainException $expected, callable $action): void
    {
        try {
            $action();
        } catch (DomainException $exception) {
            Assert::assertSame([$expected::class, $expected->parameters()], [$exception::class, $exception->parameters()]);

            return;
        }

        Assert::fail(\sprintf('%s was not thrown.', $expected::class));
    }
}
