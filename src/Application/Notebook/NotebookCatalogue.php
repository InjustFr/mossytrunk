<?php

declare(strict_types=1);

namespace App\Application\Notebook;

use App\Domain\Notebook\NotebookLine;
use App\Domain\Notebook\Words;
use App\Domain\Notebook\WrittenItem;
use App\Domain\Product\Product;
use App\Domain\Product\ProductType;

final readonly class NotebookCatalogue
{
    public const float MATCH_THRESHOLD = 0.5;

    /**
     * @param list<Product>              $products
     * @param array<string, float>       $rarity
     * @param array<string, ProductType> $types
     */
    private function __construct(
        private array $products,
        private array $rarity,
        private array $types,
    ) {
    }

    /**
     * @param list<Product> $products
     */
    public static function of(array $products): self
    {
        $frequency = [];
        $types = [];
        foreach ($products as $product) {
            foreach (array_unique(Words::of($product->displayName())) as $word) {
                $frequency[$word] = ($frequency[$word] ?? 0) + 1;
            }
            $types[(string) $product->type()->id()] = $product->type();
        }

        return new self($products, array_map(static fn (int $count): float => 1 / $count, $frequency), $types);
    }

    public function line(WrittenItem $item, string $expanded): NotebookLine
    {
        $written = Words::of($expanded);

        $product = $this->product($written);
        if (null !== $product) {
            $variant = $this->variant($product, $written);

            return new NotebookLine($item->written, $item->quantity, null === $variant ? $product->displayName() : \sprintf('%s — %s', $product->displayName(), $variant), $product->id(), $variant, $product->type()->id());
        }

        $type = $this->type($written);
        if (null !== $type) {
            return new NotebookLine($item->written, $item->quantity, $type->name(), typeId: $type->id());
        }

        return new NotebookLine($item->written, $item->quantity, $item->written);
    }

    /**
     * @param list<string> $written
     */
    private function product(array $written): ?Product
    {
        $best = [];
        $bestScore = 0.0;
        foreach ($this->products as $product) {
            $score = $this->score(Words::of($product->displayName()), $written) + (self::covers(Words::of($product->type()->name()), $written) ? 0.01 : 0.0);
            if ($score > $bestScore + \PHP_FLOAT_EPSILON) {
                $best = [$product];
                $bestScore = $score;
            } elseif (abs($score - $bestScore) <= \PHP_FLOAT_EPSILON) {
                $best[] = $product;
            }
        }

        return 1 === \count($best) && $bestScore >= self::MATCH_THRESHOLD ? $best[0] : null;
    }

    /**
     * @param list<string> $known
     * @param list<string> $written
     */
    private function score(array $known, array $written): float
    {
        $total = 0.0;
        $matched = 0.0;
        foreach (array_unique($known) as $word) {
            $weight = $this->rarity[$word] ?? 1.0;
            $total += $weight;
            if (self::mentions($written, $word)) {
                $matched += $weight;
            }
        }

        return $total > 0 ? $matched / $total : 0.0;
    }

    /**
     * @param list<string> $written
     */
    private function variant(Product $product, array $written): ?string
    {
        $variants = $product->variants();
        usort($variants, static fn (string $a, string $b): int => \strlen($b) <=> \strlen($a));
        foreach ($variants as $variant) {
            if (self::covers(Words::of($variant), $written)) {
                return $variant;
            }
        }

        return null;
    }

    /**
     * @param list<string> $written
     */
    private function type(array $written): ?ProductType
    {
        $found = array_values(array_filter($this->types, static fn (ProductType $type): bool => self::covers(Words::of($type->name()), $written)));

        return 1 === \count($found) ? $found[0] : null;
    }

    /**
     * @param list<string> $known
     * @param list<string> $written
     */
    private static function covers(array $known, array $written): bool
    {
        return [] !== $known && [] === array_filter($known, static fn (string $word): bool => !self::mentions($written, $word));
    }

    /**
     * @param list<string> $written
     */
    private static function mentions(array $written, string $known): bool
    {
        foreach ($written as $word) {
            if (Words::alike($word, $known)) {
                return true;
            }
        }

        return false;
    }
}
