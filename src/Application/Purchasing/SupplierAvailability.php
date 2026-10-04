<?php

declare(strict_types=1);

namespace App\Application\Purchasing;

use App\Domain\Purchasing\Exception\SupplierAlreadyExists;
use App\Domain\Purchasing\Supplier;
use App\Domain\Purchasing\SupplierRepository;

final readonly class SupplierAvailability
{
    public function __construct(private SupplierRepository $suppliers)
    {
    }

    public function assertNameFree(string $name, ?Supplier $owner = null): void
    {
        $namesake = $this->suppliers->findByName($name);
        if (null !== $namesake && $namesake !== $owner) {
            throw new SupplierAlreadyExists($namesake->name());
        }
    }
}
