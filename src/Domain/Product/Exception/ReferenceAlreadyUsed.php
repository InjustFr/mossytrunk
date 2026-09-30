<?php

declare(strict_types=1);

namespace App\Domain\Product\Exception;

final class ReferenceAlreadyUsed extends InvalidProduct
{
    public function __construct(string $reference)
    {
        parent::__construct('product.reference_already_used', ['reference' => $reference]);
    }
}
