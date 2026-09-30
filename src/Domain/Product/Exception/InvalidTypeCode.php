<?php

declare(strict_types=1);

namespace App\Domain\Product\Exception;

final class InvalidTypeCode extends InvalidProduct
{
    public function __construct(string $code)
    {
        parent::__construct(\sprintf('Code de type invalide « %s » (1 à 8 lettres ou chiffres).', $code));
    }
}
