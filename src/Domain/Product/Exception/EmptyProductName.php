<?php

declare(strict_types=1);

namespace App\Domain\Product\Exception;

final class EmptyProductName extends InvalidProduct
{
    public function __construct()
    {
        parent::__construct('Le nom du produit est obligatoire.');
    }
}
