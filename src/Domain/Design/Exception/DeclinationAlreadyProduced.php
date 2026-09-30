<?php

declare(strict_types=1);

namespace App\Domain\Design\Exception;

final class DeclinationAlreadyProduced extends InvalidDesign
{
    public function __construct(string $declination)
    {
        parent::__construct(\sprintf('« %s » est déjà un produit : sa déclinaison ne change plus.', $declination));
    }
}
