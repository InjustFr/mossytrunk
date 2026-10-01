<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Reference;

use App\Domain\Purchasing\SupplierOrder;
use App\Domain\Reference\ReferenceKind;

final readonly class DoctrineSupplierOrderReferences extends DoctrineReferencedItems
{
    public function kind(): ReferenceKind
    {
        return ReferenceKind::SupplierOrder;
    }

    protected function entity(): string
    {
        return SupplierOrder::class;
    }

    protected function momentField(): string
    {
        return 'orderedOn';
    }
}
