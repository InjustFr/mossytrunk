<?php

declare(strict_types=1);

namespace App\Application\Reference\ListReferenceFormats;

final readonly class ReferenceFormatView
{
    /**
     * @param list<string> $tokens
     */
    public function __construct(
        public string $kind,
        public string $template,
        public string $defaultTemplate,
        public array $tokens,
        public string $example,
        public int $existing,
    ) {
    }
}
