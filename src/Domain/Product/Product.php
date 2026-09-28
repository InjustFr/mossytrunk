<?php

declare(strict_types=1);

namespace App\Domain\Product;

use App\Domain\Shared\InvalidMoney;
use App\Domain\Shared\Money;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

/**
 * A real-world product sold at events.
 *
 * Rules (see docs/business/products.md):
 * - reference and name are required; the reference is unique (checked by the use cases).
 * - prices are never negative; the buying price defaults to 0 (unknown, e.g. after a SumUp import).
 * - variants are free-text labels (colour, size, design…), unique per product.
 * - a product without variants is a unique product.
 * - a product may have a type; it is then displayed as "{type} {name}" (e.g. "Print Forêt").
 */
#[ORM\Entity]
#[ORM\Table(name: 'product')]
class Product
{
    #[ORM\Id]
    #[ORM\Column(type: UlidType::NAME, unique: true)]
    private Ulid $id;

    #[ORM\Column(length: 64, unique: true)]
    private string $reference;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\ManyToOne(targetEntity: ProductType::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?ProductType $type = null;

    #[ORM\Embedded(class: Money::class, columnPrefix: 'selling_price_')]
    private Money $sellingPrice;

    #[ORM\Embedded(class: Money::class, columnPrefix: 'buying_price_')]
    private Money $buyingPrice;

    /** @var list<string> */
    #[ORM\Column(type: 'json')]
    private array $variants = [];

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    /**
     * @param list<string> $variants
     */
    private function __construct(Ulid $id, string $reference, string $name, Money $sellingPrice, Money $buyingPrice, array $variants, ?ProductType $type)
    {
        $this->id = $id;
        $this->createdAt = new \DateTimeImmutable();
        $this->describe($reference, $name);
        $this->type = $type;
        $this->reprice($sellingPrice, $buyingPrice);
        foreach ($variants as $variant) {
            $this->addVariant($variant);
        }
    }

    /**
     * @param list<string> $variants
     */
    public static function create(string $reference, string $name, Money $sellingPrice, ?Money $buyingPrice = null, array $variants = [], ?ProductType $type = null): self
    {
        return new self(new Ulid(), $reference, $name, $sellingPrice, $buyingPrice ?? Money::zero(), $variants, $type);
    }

    public function classify(?ProductType $type): void
    {
        $this->type = $type;
    }

    public function describe(string $reference, string $name): void
    {
        $reference = trim($reference);
        $name = trim($name);

        if ('' === $reference) {
            throw InvalidProduct::emptyReference();
        }
        if ('' === $name) {
            throw InvalidProduct::emptyName();
        }

        $this->reference = $reference;
        $this->name = $name;
    }

    public function reprice(Money $sellingPrice, Money $buyingPrice): void
    {
        if ($sellingPrice->isNegative()) {
            throw InvalidMoney::mustNotBeNegative('Le prix de vente');
        }
        if ($buyingPrice->isNegative()) {
            throw InvalidMoney::mustNotBeNegative('Le prix d\'achat');
        }

        $this->sellingPrice = $sellingPrice;
        $this->buyingPrice = $buyingPrice;
    }

    public function addVariant(string $variant): void
    {
        $variant = trim($variant);

        if ('' === $variant) {
            throw InvalidProduct::emptyVariant();
        }
        if ($this->hasVariant($variant)) {
            throw InvalidProduct::duplicateVariant($variant);
        }

        $this->variants[] = $variant;
    }

    public function removeVariant(string $variant): void
    {
        $this->variants = array_values(array_filter($this->variants, static fn (string $existing): bool => $existing !== $variant));
    }

    /**
     * Keeps the variant list in sync with the given one (order preserved).
     *
     * @param list<string> $variants
     */
    public function replaceVariants(array $variants): void
    {
        $previous = $this->variants;
        $this->variants = [];

        try {
            foreach ($variants as $variant) {
                $this->addVariant($variant);
            }
        } catch (InvalidProduct $exception) {
            $this->variants = $previous;

            throw $exception;
        }
    }

    /**
     * Validates the (product, variant) tuple a customer is buying.
     * A product with variants requires one of them; a unique product accepts none.
     */
    public function sellable(?string $variant): SellableItem
    {
        $variant = null === $variant || '' === trim($variant) ? null : trim($variant);

        if ($this->hasVariants()) {
            if (null === $variant) {
                throw InvalidProduct::variantRequired($this->displayName());
            }
            if (!$this->hasVariant($variant)) {
                throw InvalidProduct::unknownVariant($this->displayName(), $variant);
            }
        } elseif (null !== $variant) {
            throw InvalidProduct::hasNoVariants($this->displayName());
        }

        return new SellableItem($this->id, $variant, $this->displayName(), $this->sellingPrice, $this->buyingPrice);
    }

    public function hasVariants(): bool
    {
        return [] !== $this->variants;
    }

    public function hasVariant(string $variant): bool
    {
        return \in_array($variant, $this->variants, true);
    }

    public function id(): Ulid
    {
        return $this->id;
    }

    public function reference(): string
    {
        return $this->reference;
    }

    public function name(): string
    {
        return $this->name;
    }

    /**
     * How the product is shown everywhere (and snapshotted on orders): "{type} {name}", or the name alone.
     */
    public function displayName(): string
    {
        return null === $this->type ? $this->name : \sprintf('%s %s', $this->type->name(), $this->name);
    }

    public function type(): ?ProductType
    {
        return $this->type;
    }

    public function sellingPrice(): Money
    {
        return $this->sellingPrice;
    }

    public function buyingPrice(): Money
    {
        return $this->buyingPrice;
    }

    /**
     * @return list<string>
     */
    public function variants(): array
    {
        return $this->variants;
    }
}
