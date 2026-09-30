<?php

declare(strict_types=1);

namespace App\Application\Product\CreateProductType;

use App\Application\Translator;
use App\Domain\Product\ProductType;
use App\Domain\Product\ProductTypeRepository;

final readonly class MiscellaneousType
{
    public function __construct(
        private ProductTypeRepository $types,
        private ProductTypeCreator $creator,
        private Translator $translator,
    ) {
    }

    public function get(): ProductType
    {
        $name = $this->translator->trans('product_type.miscellaneous');

        return $this->types->findByName($name) ?? $this->creator->create($name, prefixesNames: false);
    }
}
