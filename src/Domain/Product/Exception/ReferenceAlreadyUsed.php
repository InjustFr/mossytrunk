<?php

declare(strict_types=1);

namespace App\Domain\Product\Exception;

final class ReferenceAlreadyUsed extends InvalidProduct
{
    public function __construct(string $reference)
    {
        parent::__construct(\sprintf('La référence « %s » est déjà utilisée par un autre produit.', $reference));
    }
}
