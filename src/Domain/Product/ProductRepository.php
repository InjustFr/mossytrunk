<?php

declare(strict_types=1);

namespace App\Domain\Product;

use Symfony\Component\Uid\Ulid;

interface ProductRepository
{
    public function add(Product $product): void;

    /**
     * @throws \App\Domain\Shared\NotFound
     */
    public function get(Ulid $id): Product;

    public function findByReference(string $reference): ?Product;

    public function findByName(string $name): ?Product;

    /**
     * @return list<Product> sorted by type name (untyped last), then name
     */
    public function all(): array;
}
