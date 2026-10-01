<?php

declare(strict_types=1);

namespace App\Domain\Reference;

interface ReferencedItems
{
    public function kind(): ReferenceKind;

    public function holds(string $reference): bool;

    public function count(): int;

    /**
     * @return list<Referenced>
     */
    public function oldestFirst(): array;
}
