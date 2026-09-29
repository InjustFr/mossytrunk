<?php

declare(strict_types=1);

namespace App\Domain\Design;

use App\Domain\Shared\InvalidMoney;
use App\Domain\Shared\Money;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

#[ORM\Entity]
#[ORM\Table(name: 'design_declination')]
class Declination
{
    #[ORM\Id]
    #[ORM\Column(type: UlidType::NAME, unique: true)]
    private Ulid $id;

    #[ORM\ManyToOne(targetEntity: Design::class, inversedBy: 'declinations')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Design $design;

    #[ORM\ManyToOne(targetEntity: Gabarit::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Gabarit $gabarit;

    #[ORM\Column(length: 255)]
    private string $productName;

    #[ORM\Embedded(class: Money::class, columnPrefix: 'selling_price_')]
    private Money $sellingPrice;

    /** @var list<string> */
    #[ORM\Column(type: Types::JSON)]
    private array $variants = [];

    /** @var list<string> */
    #[ORM\Column(type: Types::JSON)]
    private array $adaptations = [];

    /** @var list<string> */
    #[ORM\Column(type: Types::JSON)]
    private array $doneAdaptations = [];

    #[ORM\Column(type: UlidType::NAME, nullable: true)]
    private ?Ulid $productId = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    public function __construct(Design $design, Gabarit $gabarit)
    {
        $this->id = new Ulid();
        $this->design = $design;
        $this->gabarit = $gabarit;
        $this->createdAt = new \DateTimeImmutable();
        $this->adaptations = $gabarit->adaptations();
        $this->adjust($design->name(), $gabarit->sellingPrice(), $gabarit->variants());
    }

    /**
     * @param list<string> $variants
     */
    public function adjust(string $productName, Money $sellingPrice, array $variants): void
    {
        $productName = trim($productName);
        if ('' === $productName) {
            throw InvalidDesign::emptyName('du produit');
        }
        if ($sellingPrice->isNegative()) {
            throw InvalidMoney::mustNotBeNegative('Le prix');
        }

        $this->productName = $productName;
        $this->sellingPrice = $sellingPrice;
        $this->variants = array_values(array_unique(array_filter(array_map('trim', $variants), static fn (string $variant): bool => '' !== $variant)));
    }

    public function tick(string $adaptation, bool $done): void
    {
        if (!\in_array($adaptation, $this->adaptations, true)) {
            throw InvalidDesign::unknownAdaptation($adaptation);
        }

        $remaining = array_values(array_filter($this->doneAdaptations, static fn (string $existing): bool => $existing !== $adaptation));
        $this->doneAdaptations = $done ? [...$remaining, $adaptation] : $remaining;
    }

    /**
     * @return list<string>
     */
    public function pendingAdaptations(): array
    {
        return array_values(array_diff($this->adaptations, $this->doneAdaptations));
    }

    public function displayName(): string
    {
        $type = $this->gabarit->type();

        return null === $type ? $this->productName : \sprintf('%s %s', $type->name(), $this->productName);
    }

    public function linkProduct(Ulid $productId): void
    {
        $this->productId = $productId;
    }

    public function isOn(Gabarit $gabarit): bool
    {
        return $this->gabarit === $gabarit;
    }

    public function id(): Ulid
    {
        return $this->id;
    }

    public function gabarit(): Gabarit
    {
        return $this->gabarit;
    }

    public function productName(): string
    {
        return $this->productName;
    }

    public function sellingPrice(): Money
    {
        return $this->sellingPrice;
    }

    /**
     * @return list<string>
     */
    public function variants(): array
    {
        return $this->variants;
    }

    /**
     * @return list<string>
     */
    public function adaptations(): array
    {
        return $this->adaptations;
    }

    /**
     * @return list<string>
     */
    public function doneAdaptations(): array
    {
        return $this->doneAdaptations;
    }

    public function productId(): ?Ulid
    {
        return $this->productId;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
