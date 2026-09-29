<?php

declare(strict_types=1);

namespace App\Domain\Purchasing;

use Symfony\Component\Uid\Ulid;

interface SupplierRepository
{
    public function add(Supplier $supplier): void;

    public function get(Ulid $id): Supplier;

    public function findByName(string $name): ?Supplier;

    /**
     * @return list<Supplier>
     */
    public function all(): array;
}
