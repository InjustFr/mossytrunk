<?php

declare(strict_types=1);

namespace App\Application\Design\SaveDesign;

final readonly class SaveDesign
{
    /**
     * @param list<string> $gabaritIds
     */
    public function __construct(
        public ?string $designId,
        public string $name,
        public ?string $collectionId = null,
        public ?string $notes = null,
        public array $gabaritIds = [],
    ) {
    }
}
