<?php

declare(strict_types=1);

namespace App\Application\Integration\ListServices;

final readonly class FieldView
{
    public function __construct(
        public string $name,
        public string $label,
        public bool $secret,
        public bool $required,
        public ?string $pattern,
        public ?string $patternMessage,
        public ?string $hint,
        public int $maxLength,
    ) {
    }
}
