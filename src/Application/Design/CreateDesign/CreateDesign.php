<?php

declare(strict_types=1);

namespace App\Application\Design\CreateDesign;

final readonly class CreateDesign
{
    /**
     * @param list<string> $gabaritIds
     */
    public function __construct(
        public string $name,
        public ?string $collectionId = null,
        public ?string $notes = null,
        public array $gabaritIds = [],
    ) {
    }
}
