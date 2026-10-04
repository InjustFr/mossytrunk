<?php

declare(strict_types=1);

namespace App\Application\Product;

use App\Domain\Product\Exception\TypeAlreadyExists;
use App\Domain\Product\Exception\TypeCodeAlreadyUsed;
use App\Domain\Product\ProductType;
use App\Domain\Product\ProductTypeRepository;

final readonly class ProductTypeAvailability
{
    public function __construct(private ProductTypeRepository $types)
    {
    }

    public function assertNameFree(string $name, ?ProductType $owner = null): void
    {
        $holder = $this->types->findByName($name);
        if (null !== $holder && (null === $owner || !$holder->id()->equals($owner->id()))) {
            throw new TypeAlreadyExists(trim($name));
        }
    }

    public function assertCodeFree(string $code, ?ProductType $owner = null): void
    {
        if ($code !== $owner?->code() && $this->types->codeExists($code)) {
            throw new TypeCodeAlreadyUsed($code);
        }
    }
}
