<?php

declare(strict_types=1);

namespace App\Application\Product\ListProductTypes;

use App\Domain\Product\ProductType;

final readonly class ProductTypeView
{
    public function __construct(
        public string $id,
        public string $name,
        public string $code,
    ) {
    }

    public static function fromType(ProductType $type): self
    {
        return new self((string) $type->id(), $type->name(), $type->code());
    }
}
