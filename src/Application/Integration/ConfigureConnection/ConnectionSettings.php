<?php

declare(strict_types=1);

namespace App\Application\Integration\ConfigureConnection;

use App\Domain\Integration\SalesContext;
use App\Domain\Integration\UnknownItems;

final readonly class ConnectionSettings
{
    /**
     * @param array<string, string|null> $fields
     */
    public function __construct(
        public string $service,
        public array $fields,
        public ?SalesContext $salesContext = null,
        public ?UnknownItems $unknownItems = null,
    ) {
    }
}
