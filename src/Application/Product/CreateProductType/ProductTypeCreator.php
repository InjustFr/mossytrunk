<?php

declare(strict_types=1);

namespace App\Application\Product\CreateProductType;

use App\Application\Product\ProductTypeAvailability;
use App\Application\WorkspaceContext;
use App\Domain\Product\ProductType;
use App\Domain\Product\ProductTypeRepository;
use App\Domain\Product\TypeCodeGenerator;

final readonly class ProductTypeCreator
{
    public function __construct(
        private ProductTypeRepository $types,
        private ProductTypeAvailability $availability,
        private TypeCodeGenerator $codes,
        private WorkspaceContext $workspace,
    ) {
    }

    /**
     * @param list<string> $variants
     */
    public function create(string $name, ?string $color = null, ?string $code = null, array $variants = [], bool $prefixesNames = true): ProductType
    {
        $this->availability->assertNameFree($name);

        $type = ProductType::create(
            $this->workspace->current(),
            $name,
            $this->codeOf($name, $code),
            $color ?? ProductType::paletteColor(\count($this->types->all())),
        );
        $type->defineVariants($variants);
        $type->prefixNames($prefixesNames);
        $this->types->add($type);

        return $type;
    }

    private function codeOf(string $name, ?string $code): string
    {
        $code = ProductType::normalizedCode($code ?? '');
        if ('' === $code) {
            return $this->codes->generate($name);
        }
        $this->availability->assertCodeFree($code);

        return $code;
    }
}
