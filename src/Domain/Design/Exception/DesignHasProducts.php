<?php

declare(strict_types=1);

namespace App\Domain\Design\Exception;

final class DesignHasProducts extends InvalidDesign
{
    public function __construct(string $design)
    {
        parent::__construct(\sprintf('« %s » a déjà donné des produits : il ne peut pas être supprimé.', $design));
    }
}
