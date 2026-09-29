<?php

declare(strict_types=1);

namespace App\Domain\Etsy;

use Symfony\Component\Uid\Ulid;

interface EtsyListingRepository
{
    public function add(EtsyListing $listing): void;

    public function get(Ulid $id): EtsyListing;

    public function findByKey(string $key): ?EtsyListing;

    /**
     * @return list<EtsyListing>
     */
    public function all(): array;
}
