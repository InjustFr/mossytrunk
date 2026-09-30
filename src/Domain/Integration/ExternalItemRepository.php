<?php

declare(strict_types=1);

namespace App\Domain\Integration;

use Symfony\Component\Uid\Ulid;

interface ExternalItemRepository
{
    public function add(ExternalItem $item): void;

    public function get(Ulid $id): ExternalItem;

    /**
     * @return list<ExternalItem>
     */
    public function ofService(string $service): array;

    public function unlinkedCount(string $service): int;

    /**
     * @return list<ExternalItem>
     */
    public function linkedTo(Ulid $productId): array;
}
