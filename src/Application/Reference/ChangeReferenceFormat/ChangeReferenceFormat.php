<?php

declare(strict_types=1);

namespace App\Application\Reference\ChangeReferenceFormat;

use App\Domain\Reference\ReferenceKind;

final readonly class ChangeReferenceFormat
{
    public function __construct(
        public ReferenceKind $kind,
        public string $template,
        public bool $applyToExisting,
    ) {
    }
}
