<?php

declare(strict_types=1);

namespace App\Application\Integration\ListServices;

final readonly class FieldValueView
{
    public function __construct(
        public ?string $value,
        public bool $configured,
        public ?string $hint,
    ) {
    }
}
