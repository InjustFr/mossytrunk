<?php

declare(strict_types=1);

namespace App\Domain\Product;

use App\Domain\Identity\Workspace;
use App\Domain\Product\Exception\DuplicateVariant;
use App\Domain\Product\Exception\EmptyProductName;
use App\Domain\Product\Exception\EmptyProductReference;
use App\Domain\Product\Exception\InvalidProduct;
use App\Domain\Product\Exception\LastPriceKept;
use App\Domain\Product\Exception\NegativeLowStockThreshold;
use App\Domain\Product\Exception\PriceDatedInTheFuture;
use App\Domain\Product\Exception\ProductHasNoVariants;
use App\Domain\Product\Exception\ProductReferenceTooLong;
use App\Domain\Product\Exception\SupplyIsNotSold;
use App\Domain\Product\Exception\UnknownVariant;
use App\Domain\Product\Exception\VariantChoiceMissing;
use App\Domain\Product\Exception\VariantRequired;
use App\Domain\Reference\Referenced;
use App\Domain\Reference\ReferenceSubject;
use App\Domain\Sales\SalesChannel;
use App\Domain\Shared\Exception\NegativeAmount;
use App\Domain\Shared\Exception\NotFound;
use App\Domain\Shared\Money;
use App\Domain\Shared\OptionalText;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

#[ORM\Entity]
#[ORM\Table(name: 'product')]
#[ORM\UniqueConstraint(name: 'product_workspace_reference', columns: ['workspace_id', 'reference'])]
class Product implements Referenced
{
    public const int DEFAULT_LOW_STOCK_THRESHOLD = 10;
    public const int REFERENCE_MAX_LENGTH = 64;

    #[ORM\Id]
    #[ORM\Column(type: UlidType::NAME, unique: true)]
    private Ulid $id;

    #[ORM\ManyToOne(targetEntity: Workspace::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Workspace $workspace;

    #[ORM\Column(length: self::REFERENCE_MAX_LENGTH)]
    private string $reference;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\ManyToOne(targetEntity: ProductType::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ProductType $type;

    #[ORM\Embedded(class: Money::class, columnPrefix: 'selling_price_')]
    private Money $sellingPrice;

    #[ORM\Embedded(class: Money::class, columnPrefix: 'buying_price_')]
    private Money $buyingPrice;

    /** @var list<string> */
    #[ORM\Column(type: 'json')]
    private array $variants = [];

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    /** @var Collection<int, SellingPriceChange> */
    #[ORM\OneToMany(targetEntity: SellingPriceChange::class, mappedBy: 'product', cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['since' => 'ASC'])]
    private Collection $priceHistory;

    /** @var Collection<int, ChannelPrice> */
    #[ORM\OneToMany(targetEntity: ChannelPrice::class, mappedBy: 'product', cascade: ['persist'], orphanRemoval: true)]
    private Collection $channelPrices;

    #[ORM\Column(options: ['default' => self::DEFAULT_LOW_STOCK_THRESHOLD])]
    private int $lowStockThreshold = self::DEFAULT_LOW_STOCK_THRESHOLD;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $archivedAt = null;

    #[ORM\Column(length: 16, enumType: ProductKind::class, options: ['default' => 'article'])]
    private ProductKind $kind = ProductKind::Article;

    /**
     * @param list<string> $variants
     */
    private function __construct(Ulid $id, Workspace $workspace, string $reference, string $name, Money $sellingPrice, ProductType $type, array $variants, ProductKind $kind = ProductKind::Article)
    {
        $this->id = $id;
        $this->kind = $kind;
        $this->workspace = $workspace;
        $this->createdAt = new \DateTimeImmutable();
        $this->priceHistory = new ArrayCollection();
        $this->channelPrices = new ArrayCollection();
        $this->changeReference($reference);
        $this->rename($name);
        $this->type = $type;
        $this->reprice($sellingPrice);
        $this->buyingPrice = Money::zero();
        foreach ($variants as $variant) {
            $this->addVariant($variant);
        }
    }

    /**
     * @param list<string> $variants
     */
    public static function create(Workspace $workspace, string $reference, string $name, Money $sellingPrice, ProductType $type, array $variants = []): self
    {
        return new self(new Ulid(), $workspace, $reference, $name, $sellingPrice, $type, $variants);
    }

    /**
     * @param list<string> $variants
     */
    public static function supply(Workspace $workspace, string $reference, string $name, ProductType $type, array $variants = []): self
    {
        return new self(new Ulid(), $workspace, $reference, $name, Money::zero(), $type, $variants, ProductKind::Supply);
    }

    public function kind(): ProductKind
    {
        return $this->kind;
    }

    public function isSupply(): bool
    {
        return ProductKind::Supply === $this->kind;
    }

    private function assertSold(Money $price): void
    {
        if ($this->isSupply() && !$price->isZero()) {
            throw new SupplyIsNotSold($this->displayName());
        }
    }

    public function changeReference(string $reference): void
    {
        $reference = trim($reference);
        if ('' === $reference) {
            throw new EmptyProductReference();
        }
        if (mb_strlen($reference) > self::REFERENCE_MAX_LENGTH) {
            throw new ProductReferenceTooLong(self::REFERENCE_MAX_LENGTH);
        }

        $this->reference = $reference;
    }

    public function classify(ProductType $type): void
    {
        $this->type = $type;
        foreach ($this->variants as $variant) {
            $type->offerVariant($variant);
        }
    }

    public function rename(string $name): void
    {
        $name = trim($name);
        if ('' === $name) {
            throw new EmptyProductName();
        }

        $this->name = $name;
    }

    public function reprice(Money $sellingPrice): void
    {
        if ($sellingPrice->isNegative()) {
            throw new NegativeAmount('selling_price');
        }
        $this->assertSold($sellingPrice);

        if (isset($this->sellingPrice) && $this->sellingPrice->equals($sellingPrice)) {
            return;
        }

        $this->sellingPrice = $sellingPrice;
        $this->priceHistory->add(new SellingPriceChange($this, $sellingPrice, new \DateTimeImmutable()));
    }

    public function setPriceOn(SalesChannel $channel, Money $price): void
    {
        if ($this->isSupply()) {
            throw new SupplyIsNotSold($this->displayName());
        }
        if ($channel->isMain()) {
            $this->reprice($price);

            return;
        }
        if ($price->isNegative()) {
            throw new NegativeAmount('price');
        }

        $own = $this->channelPriceOn($channel);
        if (null === $own) {
            $this->channelPrices->add(new ChannelPrice($this, $channel, $price));
        } else {
            $own->change($price);
        }
    }

    public function followSellingPriceOn(SalesChannel $channel): void
    {
        $own = $this->channelPriceOn($channel);
        if (null !== $own) {
            $this->channelPrices->removeElement($own);
        }
    }

    public function priceOn(?SalesChannel $channel): Money
    {
        return (null === $channel || $channel->isMain() ? null : $this->channelPriceOn($channel)?->price()) ?? $this->sellingPrice;
    }

    public function sellableOn(?SalesChannel $channel, ?string $variant, ?\DateTimeImmutable $soldAt = null): SellableItem
    {
        if ($this->isSupply()) {
            throw new SupplyIsNotSold($this->displayName());
        }
        $own = null === $channel || $channel->isMain() ? null : $this->channelPriceOn($channel)?->price();
        $price = $own ?? (null === $soldAt ? $this->sellingPrice : $this->priceAt($soldAt));

        return $this->sellable($variant)->at($price);
    }

    public function priceAt(\DateTimeImmutable $moment): Money
    {
        $history = $this->priceHistory();
        $price = [] === $history ? $this->sellingPrice : $history[0]->price();
        foreach ($history as $change) {
            if ($change->since() > $moment) {
                break;
            }
            $price = $change->price();
        }

        return $price;
    }

    /**
     * @return list<ChannelPrice>
     */
    public function channelPrices(): array
    {
        return array_values($this->channelPrices->toArray());
    }

    private function channelPriceOn(SalesChannel $channel): ?ChannelPrice
    {
        return $this->channelPrices->findFirst(static fn (int $key, ChannelPrice $price): bool => $price->isOn($channel));
    }

    public function recordPrice(Money $price, \DateTimeImmutable $since, \DateTimeImmutable $now): void
    {
        $this->assertPastPrice($price, $since, $now);
        $this->priceHistory->add(new SellingPriceChange($this, $price, $since));
        $this->followPriceHistory();
    }

    public function amendPrice(Ulid $changeId, Money $price, \DateTimeImmutable $since, \DateTimeImmutable $now): void
    {
        $this->assertPastPrice($price, $since, $now);
        $this->priceChange($changeId)->amend($price, $since);
        $this->followPriceHistory();
    }

    public function forgetPrice(Ulid $changeId): void
    {
        if (1 === $this->priceHistory->count()) {
            throw new LastPriceKept();
        }

        $this->priceHistory->removeElement($this->priceChange($changeId));
        $this->followPriceHistory();
    }

    /**
     * @return list<SellingPriceChange>
     */
    public function priceHistory(): array
    {
        $history = array_values($this->priceHistory->toArray());
        usort($history, static fn (SellingPriceChange $a, SellingPriceChange $b): int => $a->since() <=> $b->since());

        return $history;
    }

    private function priceChange(Ulid $changeId): SellingPriceChange
    {
        return $this->priceHistory->findFirst(static fn (int $key, SellingPriceChange $change): bool => $change->id()->equals($changeId))
            ?? throw new NotFound('price', (string) $changeId);
    }

    private function assertPastPrice(Money $price, \DateTimeImmutable $since, \DateTimeImmutable $now): void
    {
        if ($price->isNegative()) {
            throw new NegativeAmount('selling_price');
        }
        $this->assertSold($price);
        if ($since > $now) {
            throw new PriceDatedInTheFuture();
        }
    }

    private function followPriceHistory(): void
    {
        $history = $this->priceHistory();
        $latest = end($history);
        if (false !== $latest) {
            $this->sellingPrice = $latest->price();
        }
    }

    public function bought(Money $unitCost): void
    {
        if ($unitCost->isNegative()) {
            throw new NegativeAmount('buying_price');
        }

        $this->buyingPrice = $unitCost;
    }

    public function alertBelow(int $threshold): void
    {
        if ($threshold < 0) {
            throw new NegativeLowStockThreshold();
        }

        $this->lowStockThreshold = $threshold;
    }

    public function archive(\DateTimeImmutable $at): void
    {
        $this->archivedAt ??= $at;
    }

    public function restore(): void
    {
        $this->archivedAt = null;
    }

    public function isArchived(): bool
    {
        return null !== $this->archivedAt || $this->type->isArchived();
    }

    public function isArchivedItself(): bool
    {
        return null !== $this->archivedAt;
    }

    public function assertVariantChosen(): void
    {
        if ([] !== $this->type->variants() && !$this->hasVariants()) {
            throw new VariantChoiceMissing($this->displayName(), $this->type->name());
        }
    }

    /**
     * @return list<string>
     */
    public function activeVariants(): array
    {
        return array_values(array_filter($this->variants, fn (string $variant): bool => !$this->type->isVariantArchived($variant)));
    }

    public function lowStockThreshold(): int
    {
        return $this->lowStockThreshold;
    }

    public function addVariant(string $variant): void
    {
        if ($this->hasVariant(VariantLabel::clean($variant))) {
            throw new DuplicateVariant(trim($variant));
        }

        $this->variants[] = $this->type->offerVariant($variant);
    }

    public function removeVariant(string $variant): void
    {
        $this->variants = array_values(array_filter($this->variants, static fn (string $existing): bool => !VariantLabel::same($existing, $variant)));
    }

    public function renameVariant(string $from, string $to): void
    {
        $this->variants = VariantLabel::renamed($this->variants, $from, $to);
    }

    /**
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

    public function sellable(?string $variant): SellableItem
    {
        $variant = OptionalText::of($variant);

        if ($this->hasVariants()) {
            if (null === $variant) {
                throw new VariantRequired($this->displayName());
            }
            $variant = $this->variantNamed($variant) ?? throw new UnknownVariant($this->displayName(), $variant);
        } elseif (null !== $variant) {
            throw new ProductHasNoVariants($this->displayName());
        }

        return new SellableItem($this->id, $variant, $this->displayName(), $this->sellingPrice, $this->buyingPrice, $this->type->id());
    }

    public function hasVariants(): bool
    {
        return [] !== $this->variants;
    }

    public function sells(?string $variant): bool
    {
        return null === $variant ? !$this->hasVariants() : $this->hasVariant($variant);
    }

    public function hasVariant(string $variant): bool
    {
        return null !== $this->variantNamed($variant);
    }

    public function variantNamed(string $variant): ?string
    {
        return VariantLabel::find($this->variants, $variant);
    }

    public function id(): Ulid
    {
        return $this->id;
    }

    public function reference(): string
    {
        return $this->reference;
    }

    public function referenceSubject(): ReferenceSubject
    {
        return ProductReferenceSubject::of($this->type, $this->name, $this->createdAt);
    }

    public function name(): string
    {
        return $this->name;
    }

    public function displayName(): string
    {
        return $this->type->nameProduct($this->name);
    }

    public function type(): ProductType
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

    public function workspace(): Workspace
    {
        return $this->workspace;
    }
}
