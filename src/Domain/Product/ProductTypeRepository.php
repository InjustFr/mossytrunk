<?php

declare(strict_types=1);

namespace App\Domain\Product;

use Symfony\Component\Uid\Ulid;

interface ProductTypeRepository
{
    public function add(ProductType $type): void;

    /**
     * @throws \App\Domain\Shared\NotFound
     */
    public function get(Ulid $id): ProductType;

    /**
     * Case-insensitive.
     */
    public function findByName(string $name): ?ProductType;

    public function codeExists(string $code): bool;

    /**
     * @return list<ProductType> sorted by name
     */
    public function all(): array;
}
