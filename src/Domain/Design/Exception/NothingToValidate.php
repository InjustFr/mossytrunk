<?php

declare(strict_types=1);

namespace App\Domain\Design\Exception;

final class NothingToValidate extends InvalidDesign
{
    public function __construct(string $design)
    {
        parent::__construct(\sprintf('Déclinez « %s » sur un nouveau gabarit avant de le valider.', $design));
    }
}
