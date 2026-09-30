<?php

declare(strict_types=1);

namespace App\Domain\Product\Exception;

final class DuplicateVariant extends InvalidProduct
{
    public function __construct(string $variant)
    {
        parent::__construct(\sprintf('La variante « %s » existe déjà pour ce produit.', $variant));
    }
}
