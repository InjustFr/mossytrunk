<?php

declare(strict_types=1);

namespace App\Domain\Product\Exception;

final class EmptyTypeName extends InvalidProduct
{
    public function __construct()
    {
        parent::__construct('Le nom du type est obligatoire.');
    }
}
