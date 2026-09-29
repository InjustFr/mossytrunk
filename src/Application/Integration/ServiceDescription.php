<?php

declare(strict_types=1);

namespace App\Application\Integration;

use App\Domain\Integration\SalesContext;
use App\Domain\Integration\UnknownItems;

final readonly class ServiceDescription
{
    /**
     * @param list<ServiceField> $fields
     */
    public function __construct(
        public string $key,
        public string $label,
        public string $summary,
        public array $fields,
        public SalesContext $defaultSalesContext,
        public UnknownItems $defaultUnknownItems,
        public LinePrices $linePrices,
        public ?string $instructions = null,
    ) {
    }

    /**
     * @return list<ServiceField>
     */
    public function secretFields(): array
    {
        return array_values(array_filter($this->fields, static fn (ServiceField $field): bool => $field->secret));
    }

    /**
     * @return list<ServiceField>
     */
    public function plainFields(): array
    {
        return array_values(array_filter($this->fields, static fn (ServiceField $field): bool => !$field->secret));
    }
}
