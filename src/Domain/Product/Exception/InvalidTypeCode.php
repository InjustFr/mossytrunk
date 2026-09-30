<?php

declare(strict_types=1);

namespace App\Domain\Product\Exception;

final class InvalidTypeCode extends InvalidProduct
{
    public function __construct(string $code)
    {
        parent::__construct('product.invalid_type_code', ['code' => $code]);
    }
}
