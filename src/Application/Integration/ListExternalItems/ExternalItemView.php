<?php

declare(strict_types=1);

namespace App\Application\Integration\ListExternalItems;

final readonly class ExternalItemView
{
    /**
     * @param array{productId: string, name: string, variant: ?string}|null $linkedTo
     */
    public function __construct(
        public string $id,
        public string $externalRef,
        public string $label,
        public ?string $variation,
        public ?array $linkedTo,
        public string $seenAt,
    ) {
    }
}
