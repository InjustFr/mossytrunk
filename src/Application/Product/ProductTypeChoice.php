<?php

declare(strict_types=1);

namespace App\Application\Product;

use App\Application\Product\CreateProductType\MiscellaneousType;
use App\Domain\Product\ProductType;
use App\Domain\Product\ProductTypeRepository;
use Symfony\Component\Uid\Ulid;

final readonly class ProductTypeChoice
{
    public function __construct(
        private ProductTypeRepository $types,
        private MiscellaneousType $miscellaneous,
    ) {
    }

    public function of(?string $typeId): ProductType
    {
        return null === $typeId || '' === $typeId ? $this->miscellaneous->get() : $this->types->get(Ulid::fromString($typeId));
    }
}
