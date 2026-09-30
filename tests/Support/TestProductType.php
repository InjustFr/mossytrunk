<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Domain\Product\ProductType;

final class TestProductType
{
    private static ?ProductType $miscellaneous = null;

    public static function get(): ProductType
    {
        if (null === self::$miscellaneous) {
            self::$miscellaneous = ProductType::create(TestWorkspace::get(), 'Divers', 'DIV');
            self::$miscellaneous->prefixNames(false);
        }

        return self::$miscellaneous;
    }
}
