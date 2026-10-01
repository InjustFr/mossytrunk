<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Reference;

use App\Domain\Product\Product;
use App\Domain\Reference\ReferenceKind;

final readonly class DoctrineProductReferences extends DoctrineReferencedItems
{
    public function kind(): ReferenceKind
    {
        return ReferenceKind::Product;
    }

    protected function entity(): string
    {
        return Product::class;
    }

    protected function momentField(): string
    {
        return 'createdAt';
    }
}
