<?php

declare(strict_types=1);

namespace App\Domain\Product;

use App\Domain\Reference\ReferenceSubject;

final class ProductReferenceSubject
{
    private const string UNTYPED_CODE = 'PRD';
    private const string NAMELESS_CODE = 'X';

    public static function of(?ProductType $type, string $name, \DateTimeImmutable $createdAt): ReferenceSubject
    {
        return ReferenceSubject::named($createdAt, $type?->code() ?? self::UNTYPED_CODE, Abbreviation::of($name, self::NAMELESS_CODE));
    }
}
