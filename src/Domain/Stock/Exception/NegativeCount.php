<?php

declare(strict_types=1);

namespace App\Domain\Stock\Exception;

final class NegativeCount extends InvalidStock
{
    public function __construct()
    {
        parent::__construct('La quantité comptée ne peut pas être négative.');
    }
}
