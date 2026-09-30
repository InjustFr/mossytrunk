<?php

declare(strict_types=1);

namespace App\Domain\Product\Exception;

final class ProductReferenceTooLong extends InvalidProduct
{
    public function __construct(int $maxLength)
    {
        parent::__construct(\sprintf('La référence ne peut pas dépasser %d caractères.', $maxLength));
    }
}
