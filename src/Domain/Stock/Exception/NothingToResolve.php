<?php

declare(strict_types=1);

namespace App\Domain\Stock\Exception;

final class NothingToResolve extends InvalidStock
{
    public function __construct()
    {
        parent::__construct('stock.nothing_to_resolve');
    }
}
