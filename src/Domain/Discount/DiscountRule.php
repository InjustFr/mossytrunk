<?php

declare(strict_types=1);

namespace App\Domain\Discount;

use App\Domain\Product\Product;
use App\Domain\Shared\InvalidMoney;
use App\Domain\Shared\Money;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

/**
 * Bundle discount: any `bundleSize` units among the eligible products cost `bundlePrice` together.
 * Example: stickers cost 4 € each, "3 stickers for 10 €" → bundleSize 3, bundlePrice 10 €.
 * Units of different eligible products (and any of their variants) can be mixed in a bundle.
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

    #[ORM\Column(length: 255)]
    private string $name;

    /** @var Collection<int, Product> */
    #[ORM\ManyToMany(targetEntity: Product::class)]
    #[ORM\JoinTable(name: 'discount_rule_product')]
    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(onDelete: 'CASCADE')]
    private Collection $eligibleProducts;

    #[ORM\Column]
    private int $bundleSize;

    #[ORM\Embedded(class: Money::class, columnPrefix: 'bundle_price_')]
    private Money $bundlePrice;

    #[ORM\Column]
    private bool $active = true;

    /**
     * @param list<Product> $eligibleProducts
     */
    private function __construct(Ulid $id, string $name, array $eligibleProducts, int $bundleSize, Money $bundlePrice)
    {
        $this->id = $id;
        $this->eligibleProducts = new ArrayCollection();
        $this->redefine($name, $eligibleProducts, $bundleSize, $bundlePrice);
    }

    /**
     * @param list<Product> $eligibleProducts
     */
    public static function create(string $name, array $eligibleProducts, int $bundleSize, Money $bundlePrice): self
    {
        return new self(new Ulid(), $name, $eligibleProducts, $bundleSize, $bundlePrice);
    }

    /**
     * @param list<Product> $eligibleProducts
     */
    public function redefine(string $name, array $eligibleProducts, int $bundleSize, Money $bundlePrice): void
    {
        $name = trim($name);
        if ('' === $name) {
            throw InvalidDiscountRule::emptyName();
        }
        if ([] === $eligibleProducts) {
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
    }

    public function activate(): void
    {
        $this->active = true;
    }

    public function deactivate(): void
    {
        $this->active = false;
    }

    public function isEligible(Ulid $productId): bool
    {
        return $this->eligibleProducts->exists(static fn (int $key, Product $product): bool => $product->id()->equals($productId));
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
}
