<?php

declare(strict_types=1);

namespace App\Domain\Product\Exception;

final class TypeCodeAlreadyUsed extends InvalidProduct
{
    public function __construct(string $code)
    {
        parent::__construct('product.type_code_already_used', ['code' => $code]);
    }
}
