<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Reference;

use App\Domain\Order\Order;
use App\Domain\Reference\ReferenceKind;

final readonly class DoctrineOrderReferences extends DoctrineReferencedItems
{
    public function kind(): ReferenceKind
    {
        return ReferenceKind::Order;
    }

    protected function entity(): string
    {
        return Order::class;
    }

    protected function momentField(): string
    {
        return 'placedAt';
    }
}
