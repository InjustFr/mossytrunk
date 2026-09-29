<?php

declare(strict_types=1);

namespace App\Domain\Purchasing;

use Symfony\Component\Uid\Ulid;

interface SupplierOrderRepository
{
    public function add(SupplierOrder $order): void;

    public function remove(SupplierOrder $order): void;

    public function get(Ulid $id): SupplierOrder;

    /**
     * @return list<SupplierOrder>
     */
    public function all(): array;
}
