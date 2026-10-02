<?php

declare(strict_types=1);

namespace App\Application\Sales;

final readonly class SalesChannelView
{
    public function __construct(
        public string $id,
        public string $name,
        public ?string $service,
        public ?string $serviceLabel,
        public string $kind,
        public bool $main,
    ) {
    }
}
