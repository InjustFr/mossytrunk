<?php

declare(strict_types=1);

namespace App\Domain\Product\Exception;

final class InvalidTypeColor extends InvalidProduct
{
    public function __construct(string $color)
    {
        parent::__construct(\sprintf('« %s » n\'est pas une couleur valide (format #rrggbb).', $color));
    }
}
