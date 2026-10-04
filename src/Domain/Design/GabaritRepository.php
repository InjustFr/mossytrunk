<?php

declare(strict_types=1);

namespace App\Domain\Design;

use App\Domain\Product\ProductType;
use Symfony\Component\Uid\Ulid;

interface GabaritRepository
{
    public function add(Gabarit $entity): void;

    public function remove(Gabarit $gabarit): void;

    public function get(Ulid $id): Gabarit;

    public function findByName(string $name): ?Gabarit;

    /**
     * @return list<Gabarit>
     */
    public function ofType(ProductType $type): array;

    /**
     * @return list<Gabarit>
     */
    public function all(): array;
}
