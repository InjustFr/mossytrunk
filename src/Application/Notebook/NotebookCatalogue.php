<?php

declare(strict_types=1);

namespace App\Application\Notebook;

use App\Domain\Notebook\NotebookLine;
use App\Domain\Product\Product;
use Symfony\Component\Uid\Ulid;

final readonly class NotebookCatalogue
{
    /**
     * @param array<string, Product> $products
     * @param array<string, string>  $types
     */
    private function __construct(
        private array $products,
        private array $types,
    ) {
    }

    /**
     * @param list<Product> $products
     */
    public static function of(array $products): self
    {
        $byId = [];
        $types = [];
        foreach ($products as $product) {
            $byId[(string) $product->id()] = $product;
            $types[(string) $product->type()->id()] = $product->type()->name();
        }

        return new self($byId, $types);
    }

    /**
     * @return list<array{id: string, name: string, products: list<array{id: string, name: string, variants: list<string>}>}>
     */
    public function types(): array
    {
        $products = [];
        foreach ($this->products as $id => $product) {
            $products[(string) $product->type()->id()][] = ['id' => $id, 'name' => $product->displayName(), 'variants' => $product->variants()];
        }

        $types = [];
        foreach ($this->types as $id => $name) {
            $types[] = ['id' => $id, 'name' => $name, 'products' => $products[$id] ?? []];
        }

        return $types;
    }

    public function line(string $written, int $quantity, ?string $productId, ?string $variant, ?string $typeId): NotebookLine
    {
        $product = $this->products[$productId ?? ''] ?? null;
        if (null !== $product) {
            $variant = null === $variant ? null : $product->variantNamed($variant);

            return new NotebookLine($written, $quantity, null === $variant ? $product->displayName() : \sprintf('%s — %s', $product->displayName(), $variant), $product->id(), $variant, $product->type()->id());
        }

        if (null !== $typeId && isset($this->types[$typeId])) {
            return new NotebookLine($written, $quantity, $this->types[$typeId], typeId: Ulid::fromString($typeId));
        }

        return new NotebookLine($written, $quantity, $written);
    }

    public function productNamed(string $name): ?string
    {
        foreach ($this->products as $id => $product) {
            if ($product->displayName() === $name) {
                return $id;
            }
        }

        return null;
    }
}
