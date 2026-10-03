<?php

declare(strict_types=1);

namespace App\Application\Notebook;

final readonly class NotebookPage
{
    public function __construct(
        public string $name,
        public string $mediaType,
        public string $content,
    ) {
    }
}
