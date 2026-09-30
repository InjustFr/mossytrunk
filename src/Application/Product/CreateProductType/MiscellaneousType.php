<?php

declare(strict_types=1);

namespace App\Application\Product\CreateProductType;

use App\Application\Translator;
use App\Domain\Product\ProductType;
use App\Domain\Product\ProductTypeRepository;

final class MiscellaneousType
{
    private ?ProductType $type = null;

    public function __construct(
        private readonly ProductTypeRepository $types,
        private readonly ProductTypeCreator $creator,
        private readonly Translator $translator,
    ) {
    }

    public function get(): ProductType
    {
        if (null === $this->type) {
            $name = $this->translator->trans('product_type.miscellaneous');
            $this->type = $this->types->findByName($name) ?? $this->creator->create($name, prefixesNames: false);
        }

        return $this->type;
    }
}
