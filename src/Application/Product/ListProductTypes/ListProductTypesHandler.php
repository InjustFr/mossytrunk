<?php

declare(strict_types=1);

namespace App\Application\Product\ListProductTypes;

use App\Domain\Product\ProductTypeRepository;

final readonly class ListProductTypesHandler
{
    public function __construct(private ProductTypeRepository $types)
    {
    }

    /**
     * @return list<ProductTypeView>
     */
    public function __invoke(): array
    {
        return array_map(ProductTypeView::fromType(...), $this->types->all());
    }
}
