<?php

declare(strict_types=1);

namespace App\Domain\Reference;

interface ReferenceFormatRepository
{
    public function of(ReferenceKind $kind): ReferenceFormat;
}
