<?php

declare(strict_types=1);

namespace App\Domain\Stock\Exception;

final class CountedTwice extends InvalidStock
{
    public function __construct(string $label)
    {
        parent::__construct('stock.counted_twice', ['item' => $label]);
    }
}
