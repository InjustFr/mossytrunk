<?php

declare(strict_types=1);

namespace App\Application\Integration\ImportSales;

use App\Application\Product\CreateProductType\ProductTypeCreator;
use App\Domain\Identity\Workspace;
use App\Domain\Product\Product;
use App\Domain\Product\ProductReferenceGenerator;
use App\Domain\Product\ProductRepository;
use App\Domain\Product\ProductType;
use App\Domain\Product\ProductTypeRepository;
use App\Domain\Shared\Money;
use Symfony\Component\Uid\Ulid;

final class ImportedCatalogue
{
    /** @var array<string, Product>|null */
    private ?array $byDisplayName = null;

    /** @var array<string, Product> */
    private array $created = [];

    /** @var array<string, ProductType> */
    private array $types = [];

    private int $typesCreated = 0;

    public function __construct(
        private readonly ProductRepository $products,
        private readonly ProductReferenceGenerator $references,
        private readonly ProductTypeRepository $typeRepository,
        private readonly ProductTypeCreator $typeCreator,
        private readonly Workspace $workspace,
        private readonly string $freeAmountName,
    ) {
    }

    public function named(string $displayName): ?Product
    {
        return $this->index()[mb_strtolower(trim($displayName))] ?? null;
    }

    public function withReference(string $reference): ?Product
    {
        $reference = trim($reference);

        return '' === $reference ? null : $this->products->findByReference($reference);
    }

    public function withId(Ulid $id): ?Product
    {
        return $this->products->findByIds([$id])[0] ?? null;
    }

    public function wasCreated(Product $product): bool
    {
        return isset($this->created[(string) $product->id()]);
    }

    public function createFor(string $name, ?string $category, Money $sellingPrice): Product
    {
        $type = null === $category || '' === trim($category) ? null : $this->type(trim($category));

        return $this->create(null === $type ? $name : self::withoutPrefix($name, $type->name()), $sellingPrice, $type);
    }

    public function freeAmount(Money $price): Product
    {
        $product = $this->named($this->freeAmountName);

        return null !== $product && !$product->hasVariants() ? $product : $this->create($this->freeAmountName, $price, null);
    }

    public function createdCount(): int
    {
        return \count($this->created);
    }

    public function typesCreatedCount(): int
    {
        return $this->typesCreated;
    }

    private function create(string $name, Money $sellingPrice, ?ProductType $type): Product
    {
        $product = Product::create($this->workspace, $this->references->generate($type, $name), $name, $sellingPrice, [], $type);
        $this->products->add($product);
        $this->index()[mb_strtolower($product->displayName())] = $product;
        $this->created[(string) $product->id()] = $product;

        return $product;
    }

    /**
     * @return array<string, Product>
     */
    private function &index(): array
    {
        if (null === $this->byDisplayName) {
            $this->byDisplayName = [];
            foreach ($this->products->all() as $product) {
                $this->byDisplayName[mb_strtolower($product->displayName())] ??= $product;
            }
        }

        return $this->byDisplayName;
    }

    private function type(string $name): ProductType
    {
        $key = mb_strtolower($name);
        if (!isset($this->types[$key])) {
            $existing = $this->typeRepository->findByName($name);
            if (null === $existing) {
                $existing = $this->typeCreator->create($name);
                ++$this->typesCreated;
            }
            $this->types[$key] = $existing;
        }

        return $this->types[$key];
    }

    private static function withoutPrefix(string $name, string $typeName): string
    {
        $prefix = $typeName.' ';
        if (0 === mb_stripos($name, $prefix) && '' !== trim(mb_substr($name, mb_strlen($prefix)))) {
            return trim(mb_substr($name, mb_strlen($prefix)));
        }

        return $name;
    }
}
