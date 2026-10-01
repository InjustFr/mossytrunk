<?php

declare(strict_types=1);

namespace App\Application\Product;

use App\Application\Reference\ReferenceGenerator;
use App\Domain\Product\ProductReferenceSubject;
use App\Domain\Product\ProductType;
use App\Domain\Reference\ReferenceKind;
use Psr\Clock\ClockInterface;

final readonly class ProductReferenceGenerator
{
    public function __construct(
        private ReferenceGenerator $references,
        private ClockInterface $clock,
    ) {
    }

    public function generate(?ProductType $type, string $name): string
    {
        return $this->references->next(ReferenceKind::Product, ProductReferenceSubject::of($type, $name, $this->clock->now()));
    }
}
