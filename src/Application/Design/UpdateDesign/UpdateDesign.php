<?php

declare(strict_types=1);

namespace App\Application\Design\UpdateDesign;

final readonly class UpdateDesign
{
    public function __construct(
        public string $designId,
        public string $name,
        public ?string $collectionId = null,
        public ?string $notes = null,
    ) {
    }
}
