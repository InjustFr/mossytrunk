<?php

declare(strict_types=1);

namespace App\Domain\Discount;

use App\Domain\Identity\Workspace;
use App\Domain\Product\Product;
use App\Domain\Product\ProductType;
use App\Domain\Shared\InvalidMoney;
use App\Domain\Shared\Money;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

/**
 * Bundle discount: any `bundleSize` eligible units cost `bundlePrice` together.
 * Example: stickers cost 4 € each, "3 stickers for 10 €" → bundleSize 3, bundlePrice 10 €.
 * Eligible = one of the listed products, or any product of one of the listed types (e.g. all Prints
 * and Stickers). Units of different eligible products (and any of their variants) can be mixed.
 *
 * See docs/business/discounts.md and {@see DiscountCalculator}.
 */
#[ORM\Entity]
#[ORM\Table(name: 'discount_rule')]
class DiscountRule
{
    #[ORM\Id]
    #[ORM\Column(type: UlidType::NAME, unique: true)]
    private Ulid $id;

    #[ORM\ManyToOne(targetEntity: Workspace::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Workspace $workspace;

    #[ORM\Column(length: 255)]
    private string $name;

    /** @var Collection<int, Product> */
    #[ORM\ManyToMany(targetEntity: Product::class)]
    #[ORM\JoinTable(name: 'discount_rule_product')]
    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(onDelete: 'CASCADE')]
    private Collection $eligibleProducts;

    /** @var Collection<int, ProductType> */
    #[ORM\ManyToMany(targetEntity: ProductType::class)]
    #[ORM\JoinTable(name: 'discount_rule_product_type')]
    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(onDelete: 'CASCADE')]
    private Collection $eligibleTypes;

    #[ORM\Column]
    private int $bundleSize;

    #[ORM\Embedded(class: Money::class, columnPrefix: 'bundle_price_')]
    private Money $bundlePrice;

    #[ORM\Column]
    private bool $active = true;

    /**
     * @param list<Product>     $eligibleProducts
     * @param list<ProductType> $eligibleTypes
     */
    private function __construct(Ulid $id, Workspace $workspace, string $name, array $eligibleProducts, int $bundleSize, Money $bundlePrice, array $eligibleTypes)
    {
        $this->id = $id;
        $this->workspace = $workspace;
        $this->eligibleProducts = new ArrayCollection();
        $this->eligibleTypes = new ArrayCollection();
        $this->redefine($name, $eligibleProducts, $bundleSize, $bundlePrice, $eligibleTypes);
    }

    /**
     * @param list<Product>     $eligibleProducts
     * @param list<ProductType> $eligibleTypes
     */
    public static function create(Workspace $workspace, string $name, array $eligibleProducts, int $bundleSize, Money $bundlePrice, array $eligibleTypes = []): self
    {
        return new self(new Ulid(), $workspace, $name, $eligibleProducts, $bundleSize, $bundlePrice, $eligibleTypes);
    }

    /**
     * @param list<Product>     $eligibleProducts
     * @param list<ProductType> $eligibleTypes
     */
    public function redefine(string $name, array $eligibleProducts, int $bundleSize, Money $bundlePrice, array $eligibleTypes = []): void
    {
        $name = trim($name);
        if ('' === $name) {
            throw InvalidDiscountRule::emptyName();
        }
        if ([] === $eligibleProducts && [] === $eligibleTypes) {
            throw InvalidDiscountRule::noEligibleProduct();
        }
        if ($bundleSize < 2) {
            throw InvalidDiscountRule::bundleTooSmall();
        }
        if (!$bundlePrice->isPositive()) {
            throw InvalidMoney::mustBePositive('Le prix du lot');
        }

        $this->name = $name;
        $this->bundleSize = $bundleSize;
        $this->bundlePrice = $bundlePrice;
        $this->eligibleProducts->clear();
        foreach ($eligibleProducts as $product) {
            if (!$this->eligibleProducts->contains($product)) {
                $this->eligibleProducts->add($product);
            }
        }
        $this->eligibleTypes->clear();
        foreach ($eligibleTypes as $type) {
            if (!$this->eligibleTypes->contains($type)) {
                $this->eligibleTypes->add($type);
            }
        }
    }

    public function activate(): void
    {
        $this->active = true;
    }

    public function deactivate(): void
    {
        $this->active = false;
    }

    public function isEligible(Ulid $productId, ?Ulid $typeId = null): bool
    {
        return $this->eligibleProducts->exists(static fn (int $key, Product $product): bool => $product->id()->equals($productId))
            || (null !== $typeId && $this->eligibleTypes->exists(static fn (int $key, ProductType $type): bool => $type->id()->equals($typeId)));
    }

    /**
     * @return list<ProductType>
     */
    public function eligibleTypes(): array
    {
        return array_values($this->eligibleTypes->toArray());
    }

    public function id(): Ulid
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function bundleSize(): int
    {
        return $this->bundleSize;
    }

    public function bundlePrice(): Money
    {
        return $this->bundlePrice;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    /**
     * @return list<Product>
     */
    public function eligibleProducts(): array
    {
        return array_values($this->eligibleProducts->toArray());
    }

    public function workspace(): Workspace
    {
        return $this->workspace;
    }
}
