<?php

declare(strict_types=1);

namespace App\Domain\Design;

use Symfony\Component\Uid\Ulid;

interface DesignCollectionRepository
{
    public function add(DesignCollection $entity): void;

    public function remove(DesignCollection $collection): void;

    public function get(Ulid $id): DesignCollection;

    /**
     * @return list<DesignCollection>
     */
    public function all(): array;
}
