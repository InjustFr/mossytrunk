<?php

declare(strict_types=1);

namespace App\Domain\Product;

use Symfony\Component\Uid\Ulid;

interface ProductRepository
{
    public function add(Product $product): void;

    public function remove(Product $product): void;

    /**
     * @throws \App\Domain\Shared\Exception\NotFound
     */
    public function get(Ulid $id): Product;

    public function findByReference(string $reference): ?Product;

    public function findByName(string $name): ?Product;

    /**
     * @return list<Product>
     */
    public function ofType(ProductType $type): array;

    /**
     * @param list<Ulid> $ids
     *
     * @return list<Product> the existing ones, in no particular order
     */
    public function findByIds(array $ids): array;

    /**
     * @return list<Product> sorted by type name (untyped last), then name
     */
    public function all(): array;

    /**
     * @return list<Product> sorted like all(), with their selling price history loaded
     */
    public function catalogue(): array;

    /**
     * @return list<Product> the products that are sold (no supplies), sorted like all()
     */
    public function articles(): array;
}
