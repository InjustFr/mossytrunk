<?php

declare(strict_types=1);

namespace App\Domain\Purchasing\Exception;

final class OrderedTwice extends InvalidPurchase
{
    public function __construct(string $label)
    {
        parent::__construct('purchasing.ordered_twice', ['item' => $label]);
    }
}
