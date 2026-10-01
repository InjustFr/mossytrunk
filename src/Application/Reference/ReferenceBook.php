<?php

declare(strict_types=1);

namespace App\Application\Reference;

use App\Domain\Reference\ReferencedItems;
use App\Domain\Reference\ReferenceKind;

final readonly class ReferenceBook
{
    /**
     * @param iterable<ReferencedItems> $items
     */
    public function __construct(private iterable $items)
    {
    }

    public function of(ReferenceKind $kind): ReferencedItems
    {
        foreach ($this->items as $items) {
            if ($kind === $items->kind()) {
                return $items;
            }
        }

        throw new \LogicException(\sprintf('No referenced items registered for "%s".', $kind->value));
    }
}
