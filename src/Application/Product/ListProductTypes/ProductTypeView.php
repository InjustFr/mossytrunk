<?php

declare(strict_types=1);

namespace App\Application\Product\ListProductTypes;

use App\Domain\Product\ProductType;

final readonly class ProductTypeView
{
    /**
     * @param list<string> $variants
     */
    public function __construct(
        public string $id,
        public string $name,
        public string $code,
        public string $color,
        public array $variants,
        public bool $prefixesNames,
    ) {
    }

    public static function fromType(ProductType $type): self
    {
        return new self((string) $type->id(), $type->name(), $type->code(), $type->color(), $type->variants(), $type->prefixesNames());
    }
}
