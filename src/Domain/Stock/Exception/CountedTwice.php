<?php

declare(strict_types=1);

namespace App\Domain\Stock\Exception;

final class CountedTwice extends InvalidStock
{
    public function __construct(string $label)
    {
        parent::__construct(\sprintf('« %s » est compté deux fois.', $label));
    }
}
