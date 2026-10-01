<?php

declare(strict_types=1);

namespace App\Domain\Sales;

use Symfony\Component\Uid\Ulid;

interface SalesChannelRepository
{
    public function add(SalesChannel $channel): void;

    public function remove(SalesChannel $channel): void;

    public function get(Ulid $id): SalesChannel;

    public function linkedTo(string $service): ?SalesChannel;

    public function main(): SalesChannel;

    /**
     * @return list<SalesChannel>
     */
    public function all(): array;
}
