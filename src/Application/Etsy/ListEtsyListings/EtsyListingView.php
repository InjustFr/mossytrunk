<?php

declare(strict_types=1);

namespace App\Application\Etsy\ListEtsyListings;

final readonly class EtsyListingView
{
    /**
     * @param array{productId: string, name: string, variant: ?string}|null $linkedTo
     */
    public function __construct(
        public string $id,
        public string $listingId,
        public string $title,
        public ?string $variation,
        public ?array $linkedTo,
        public string $seenAt,
    ) {
    }
}
