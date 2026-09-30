<?php

declare(strict_types=1);

namespace App\Domain\Product;

final readonly class TypeCodeGenerator
{
    public function __construct(private ProductTypeRepository $types)
    {
    }

    public function generate(string $name): string
    {
        $base = ProductType::codeFor($name);
        $code = $base;
        for ($i = 2; $this->types->codeExists($code); ++$i) {
            $code = $base.$i;
        }

        return $code;
    }
}
