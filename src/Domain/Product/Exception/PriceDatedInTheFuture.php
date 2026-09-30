<?php

declare(strict_types=1);

namespace App\Domain\Product\Exception;

final class PriceDatedInTheFuture extends InvalidProduct
{
    public function __construct()
    {
        parent::__construct('product.price_dated_in_the_future');
    }
}
