<?php

declare(strict_types=1);

namespace App\Application\Integration\ListServices;

final readonly class ConnectionView
{
    /**
     * @param array<string, FieldValueView> $values
     */
    public function __construct(
        public array $values,
        public string $salesContext,
        public string $unknownItems,
        public bool $configured,
        public bool $authorized,
        public ?string $accountName,
        public int $itemsToLink,
    ) {
    }
}
