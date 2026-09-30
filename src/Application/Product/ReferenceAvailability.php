<?php

declare(strict_types=1);

namespace App\Application\Product;

use App\Domain\Product\Exception\ReferenceAlreadyUsed;
use App\Domain\Product\Product;
use App\Domain\Product\ProductRepository;

final readonly class ReferenceAvailability
{
    public function __construct(private ProductRepository $products)
    {
    }

    public function assertAvailable(string $reference, ?Product $owner = null): void
    {
        $holder = $this->products->findByReference(trim($reference));
        if (null !== $holder && (null === $owner || !$holder->id()->equals($owner->id()))) {
            throw new ReferenceAlreadyUsed(trim($reference));
        }
    }
}
