<?php

declare(strict_types=1);

namespace App\Domain\Product\Exception;

final class PriceDatedInTheFuture extends InvalidProduct
{
    public function __construct()
    {
        parent::__construct('Un prix ne peut pas être daté dans le futur.');
    }
}
