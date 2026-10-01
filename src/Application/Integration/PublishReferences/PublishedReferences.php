<?php

declare(strict_types=1);

namespace App\Application\Integration\PublishReferences;

final readonly class PublishedReferences
{
    public function __construct(
        public string $label,
        public int $itemsLinked,
        public int $listingsUpdated,
    ) {
    }
}
