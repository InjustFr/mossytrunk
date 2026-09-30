<?php

declare(strict_types=1);

namespace App\Domain\Product;

/**
 * Suggests the reference of a product from its type and name: "{TYPE CODE}-{first three letters of the name}",
 * e.g. PRI-FOR ("PRD-…" when untyped). A numeric suffix keeps it unique (PRI-FOR-2). The user may change it.
 */
final class ProductReferenceGenerator
{
    /** @var array<string, true> references handed out during this request but maybe not flushed yet */
    private array $reserved = [];

    public function __construct(private readonly ProductRepository $products)
    {
    }

    public function generate(?ProductType $type, string $name): string
    {
        $base = \sprintf('%s-%s', $type?->code() ?? 'PRD', Abbreviation::of($name, 'X'));
        $reference = $base;

        for ($i = 2; isset($this->reserved[$reference]) || null !== $this->products->findByReference($reference); ++$i) {
            $reference = \sprintf('%s-%d', $base, $i);
        }

        $this->reserved[$reference] = true;

        return $reference;
    }
}
