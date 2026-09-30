<?php

declare(strict_types=1);

namespace App\Application\Product\SuggestProductReference;

use App\Domain\Product\ProductReferenceGenerator;
use App\Domain\Product\ProductTypeRepository;
use Symfony\Component\Uid\Ulid;

final readonly class SuggestProductReferenceHandler
{
    public function __construct(
        private ProductTypeRepository $types,
        private ProductReferenceGenerator $references,
    ) {
    }

    public function __invoke(?Ulid $typeId, string $name): string
    {
        return $this->references->generate(null === $typeId ? null : $this->types->get($typeId), $name);
    }
}
