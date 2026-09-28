<?php

declare(strict_types=1);

namespace App\Domain\Product;

/**
 * Builds the reference of a new product from its type and name: "{TYPE CODE}-{NAME}", e.g. PRI-FORET
 * ("PRD-…" when untyped). A numeric suffix keeps it unique (PRI-FORET-2). The reference is then fixed.
 */
final class ProductReferenceGenerator
{
    private const int MAX_SLUG_LENGTH = 40;

    /** @var array<string, true> references handed out during this request but maybe not flushed yet */
    private array $reserved = [];

    public function __construct(private readonly ProductRepository $products)
    {
    }

    public function generate(?ProductType $type, string $name): string
    {
        $base = \sprintf('%s-%s', $type?->code() ?? 'PRD', self::slug($name));
        $reference = $base;

        for ($i = 2; isset($this->reserved[$reference]) || null !== $this->products->findByReference($reference); ++$i) {
            $reference = \sprintf('%s-%d', $base, $i);
        }

        $this->reserved[$reference] = true;

        return $reference;
    }

    public static function slug(string $name): string
    {
        $ascii = (string) iconv('UTF-8', 'ASCII//TRANSLIT', $name);
        $slug = trim((string) preg_replace('/[^A-Z0-9]+/', '-', strtoupper($ascii)), '-');

        return '' === $slug ? 'X' : rtrim(substr($slug, 0, self::MAX_SLUG_LENGTH), '-');
    }
}
