<?php

declare(strict_types=1);

namespace App\Domain\Design;

use App\Domain\Design\Exception\EmptyDesignName;
use App\Domain\Identity\Workspace;
use App\Domain\Product\ProductType;
use App\Domain\Product\VariantLabel;
use App\Domain\Shared\Exception\NegativeAmount;
use App\Domain\Shared\Money;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

#[ORM\Entity]
#[ORM\Table(name: 'gabarit')]
#[ORM\UniqueConstraint(name: 'gabarit_workspace_name', columns: ['workspace_id', 'name'])]
class Gabarit
{
    #[ORM\Id]
    #[ORM\Column(type: UlidType::NAME, unique: true)]
    private Ulid $id;

    #[ORM\ManyToOne(targetEntity: Workspace::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Workspace $workspace;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\ManyToOne(targetEntity: ProductType::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ProductType $type;

    #[ORM\Embedded(class: Money::class, columnPrefix: 'selling_price_')]
    private Money $sellingPrice;

    /** @var list<string> */
    #[ORM\Column(type: Types::JSON)]
    private array $variants = [];

    /** @var list<string> */
    #[ORM\Column(type: Types::JSON)]
    private array $adaptations = [];

    /**
     * @param list<string> $variants
     * @param list<string> $adaptations
     */
    private function __construct(Workspace $workspace, string $name, ProductType $type, Money $sellingPrice, array $variants, array $adaptations)
    {
        $this->id = new Ulid();
        $this->workspace = $workspace;
        $this->describe($name, $type, $sellingPrice, $variants, $adaptations);
    }

    /**
     * @param list<string> $variants
     * @param list<string> $adaptations
     */
    public static function create(Workspace $workspace, string $name, ProductType $type, Money $sellingPrice, array $variants = [], array $adaptations = []): self
    {
        return new self($workspace, $name, $type, $sellingPrice, $variants, $adaptations);
    }

    /**
     * @param list<string> $variants
     * @param list<string> $adaptations
     */
    public function describe(string $name, ProductType $type, Money $sellingPrice, array $variants, array $adaptations): void
    {
        $name = trim($name);
        if ('' === $name) {
            throw new EmptyDesignName('template');
        }
        if ($sellingPrice->isNegative()) {
            throw new NegativeAmount('price');
        }

        $this->name = $name;
        $this->type = $type;
        $this->sellingPrice = $sellingPrice;
        $this->variants = $type->offerVariants($variants);
        $this->adaptations = TextList::clean($adaptations);
    }

    public function id(): Ulid
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function renameVariant(string $from, string $to): void
    {
        $this->variants = VariantLabel::renamed($this->variants, $from, $to);
    }

    public function usesVariant(string $variant): bool
    {
        return null !== VariantLabel::find($this->variants, $variant);
    }

    public function type(): ProductType
    {
        return $this->type;
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

    public function workspace(): Workspace
    {
        return $this->workspace;
    }
}
