<?php

declare(strict_types=1);

namespace App\Domain\Design;

use Symfony\Component\Uid\Ulid;

interface DesignRepository
{
    public function add(Design $entity): void;

    public function get(Ulid $id): Design;

    public function remove(Design $design): void;

    public function findByProduct(Ulid $productId): ?Design;

    /**
     * @return list<Design>
     */
    public function inCollection(Ulid $collectionId): array;

    /**
     * @return list<Design>
     */
    public function all(): array;
}
