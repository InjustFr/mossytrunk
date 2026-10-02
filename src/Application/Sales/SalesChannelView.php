<?php

declare(strict_types=1);

namespace App\Application\Sales;

final readonly class SalesChannelView
{
    /**
     * @param list<array{id: string, name: string, variants: list<string>}> $supplies
     */
    public function __construct(
        public string $id,
        public string $name,
        public ?string $service,
        public ?string $serviceLabel,
        public string $kind,
        public bool $main,
        public array $supplies,
    ) {
    }
}
