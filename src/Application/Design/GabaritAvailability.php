<?php

declare(strict_types=1);

namespace App\Application\Design;

use App\Domain\Design\Exception\GabaritAlreadyExists;
use App\Domain\Design\Gabarit;
use App\Domain\Design\GabaritRepository;

final readonly class GabaritAvailability
{
    public function __construct(private GabaritRepository $gabarits)
    {
    }

    public function assertNameFree(string $name, ?Gabarit $owner = null): void
    {
        $namesake = $this->gabarits->findByName($name);
        if (null !== $namesake && $namesake !== $owner) {
            throw new GabaritAlreadyExists($namesake->name());
        }
    }
}
