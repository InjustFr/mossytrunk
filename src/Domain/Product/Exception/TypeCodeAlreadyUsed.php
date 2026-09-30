<?php

declare(strict_types=1);

namespace App\Domain\Product\Exception;

final class TypeCodeAlreadyUsed extends InvalidProduct
{
    public function __construct(string $code)
    {
        parent::__construct(\sprintf('Le code « %s » est déjà utilisé par un autre type.', $code));
    }
}
