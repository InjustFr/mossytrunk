<?php

declare(strict_types=1);

namespace App\Domain\Product;

use Symfony\Component\Uid\Ulid;

interface ProductRepository
{
    public function add(Product $product): void;

    public function remove(Product $product): void;

    public function get(Ulid $id): Product;

    public function find(Ulid $id): ?Product;

    public function findByReference(string $reference): ?Product;

    /**
     * @return list<Product>
     */
    public function ofType(ProductType $type): array;

    /**
     * @param list<Ulid> $ids
     *
     * @return array<string, Product>
     */
    public function findByIds(array $ids): array;

    /**
     * @return list<Product>
     */
    public function all(): array;

    /**
     * @return list<Product>
     */
    public function catalogue(): array;

    /**
     * @return list<Product>
     */
    public function articles(): array;
}
