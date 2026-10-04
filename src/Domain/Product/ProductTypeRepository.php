<?php

declare(strict_types=1);

namespace App\Domain\Product;

use Symfony\Component\Uid\Ulid;

interface ProductTypeRepository
{
    public function add(ProductType $type): void;

    public function remove(ProductType $type): void;

    public function get(Ulid $id): ProductType;

    public function findByName(string $name): ?ProductType;

    public function codeExists(string $code): bool;

    /**
     * @return list<ProductType>
     */
    public function all(): array;
}
