<?php

declare(strict_types=1);

namespace App\Application\Product\CreateProductType;

use App\Application\WorkspaceContext;
use App\Domain\Product\Exception\TypeAlreadyExists;
use App\Domain\Product\ProductType;
use App\Domain\Product\ProductTypeRepository;

final readonly class ProductTypeCreator
{
    public function __construct(
        private ProductTypeRepository $types,
        private WorkspaceContext $workspace,
    ) {
    }

    public function create(string $name, ?string $color = null): ProductType
    {
        if (null !== $this->types->findByName($name)) {
            throw new TypeAlreadyExists(trim($name));
        }

        $type = ProductType::create(
            $this->workspace->current(),
            $name,
            $this->uniqueCodeFor($name),
            $color ?? ProductType::paletteColor(\count($this->types->all())),
        );
        $this->types->add($type);

        return $type;
    }

    private function uniqueCodeFor(string $name): string
    {
        $base = ProductType::codeFor($name);
        $code = $base;
        for ($i = 2; $this->types->codeExists($code); ++$i) {
            $code = $base.$i;
        }

        return $code;
    }
}
