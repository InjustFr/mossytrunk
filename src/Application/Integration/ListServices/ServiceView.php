<?php

declare(strict_types=1);

namespace App\Application\Integration\ListServices;

final readonly class ServiceView
{
    /**
     * @param list<FieldView> $fields
     */
    public function __construct(
        public string $key,
        public string $label,
        public string $summary,
        public ?string $instructions,
        public bool $authorizes,
        public array $fields,
        public string $defaultSalesContext,
        public string $defaultUnknownItems,
        public ?ConnectionView $connection,
    ) {
    }
}
