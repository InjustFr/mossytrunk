<?php

declare(strict_types=1);

namespace App\Domain\Product;

use App\Domain\Identity\Workspace;
use App\Domain\Product\Exception\DuplicateTypeVariant;
use App\Domain\Product\Exception\EmptyTypeName;
use App\Domain\Product\Exception\InvalidTypeCode;
use App\Domain\Product\Exception\InvalidTypeColor;
use App\Domain\Product\Exception\UnknownTypeVariant;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

#[ORM\Entity]
#[ORM\Table(name: 'product_type')]
#[ORM\UniqueConstraint(name: 'product_type_workspace_name', columns: ['workspace_id', 'name'])]
#[ORM\UniqueConstraint(name: 'product_type_workspace_code', columns: ['workspace_id', 'code'])]
class ProductType
{
    public const array PALETTE = ['#5b7f3a', '#b5654a', '#4f6d8f', '#c29a2e', '#8a6d8f', '#2f7f7a', '#7a5238', '#c07a8a', '#a3485a', '#6b7a2f', '#3f5a4c', '#9c7b5b'];

    #[ORM\Id]
    #[ORM\Column(type: UlidType::NAME, unique: true)]
    private Ulid $id;

    #[ORM\ManyToOne(targetEntity: Workspace::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Workspace $workspace;

    #[ORM\Column(length: 100)]
    private string $name;

    #[ORM\Column(length: 8)]
    private string $code;

    #[ORM\Column(length: 7)]
    private string $color;

    /** @var list<string> */
    #[ORM\Column(type: Types::JSON, options: ['default' => '[]'])]
    private array $variants = [];

    #[ORM\Column(options: ['default' => true])]
    private bool $prefixesNames = true;

    /** @var list<string> */
    #[ORM\Column(type: Types::JSON, options: ['default' => '[]'])]
    private array $archivedVariants = [];

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $archivedAt = null;

    private function __construct(Ulid $id, Workspace $workspace, string $name, string $code, string $color)
    {
        $this->id = $id;
        $this->workspace = $workspace;
        $this->rename($name);
        $this->recolor($color);
        $this->recode($code);
    }

    public static function create(Workspace $workspace, string $name, string $code, string $color = self::PALETTE[0]): self
    {
        return new self(new Ulid(), $workspace, $name, $code, $color);
    }

    public function offerVariant(string $variant): string
    {
        $variant = VariantLabel::clean($variant);
        $existing = VariantLabel::find($this->variants, $variant);
        if (null !== $existing) {
            return $existing;
        }

        $this->variants[] = $variant;

        return $variant;
    }

    /**
     * @param list<string> $variants
     *
     * @return list<string>
     */
    public function offerVariants(array $variants): array
    {
        $offered = [];
        foreach ($variants as $variant) {
            if ('' !== trim($variant)) {
                $offered[] = $this->offerVariant($variant);
            }
        }

        return array_values(array_unique($offered));
    }

    /**
     * @param list<string> $variants
     */
    public function defineVariants(array $variants): void
    {
        $defined = [];
        foreach ($variants as $variant) {
            $variant = VariantLabel::clean($variant);
            if (null !== VariantLabel::find($defined, $variant)) {
                throw new DuplicateTypeVariant($variant);
            }
            $defined[] = $variant;
        }

        $this->variants = $defined;
        $this->archivedVariants = array_values(array_filter($this->archivedVariants, static fn (string $archived): bool => null !== VariantLabel::find($defined, $archived)));
    }

    /**
     * @param list<string> $variants
     */
    public function archiveVariants(array $variants): void
    {
        $archived = [];
        foreach ($variants as $variant) {
            $known = VariantLabel::find($this->variants, $variant) ?? throw new UnknownTypeVariant($this->name, trim($variant));
            if (null === VariantLabel::find($archived, $known)) {
                $archived[] = $known;
            }
        }

        $this->archivedVariants = $archived;
    }

    public function isVariantArchived(string $variant): bool
    {
        return null !== VariantLabel::find($this->archivedVariants, $variant);
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
        return null !== $this->archivedAt;
    }

    public function renameVariant(string $from, string $to): string
    {
        $current = VariantLabel::find($this->variants, $from) ?? throw new UnknownTypeVariant($this->name, $from);
        $to = VariantLabel::clean($to);
        $namesake = VariantLabel::find($this->variants, $to);
        if (null !== $namesake && $namesake !== $current) {
            throw new DuplicateTypeVariant($to);
        }

        $this->variants = array_map(static fn (string $variant): string => $variant === $current ? $to : $variant, $this->variants);
        $this->archivedVariants = VariantLabel::renamed($this->archivedVariants, $current, $to);

        return $current;
    }

    public function prefixNames(bool $prefixes): void
    {
        $this->prefixesNames = $prefixes;
    }

    public function nameProduct(string $productName): string
    {
        return $this->prefixesNames ? \sprintf('%s %s', $this->name, $productName) : $productName;
    }

    public static function paletteColor(int $index): string
    {
        return self::PALETTE[$index % \count(self::PALETTE)];
    }

    public static function codeFor(string $name): string
    {
        return Abbreviation::of($name, 'TYP');
    }

    public static function normalizedCode(string $code): string
    {
        return strtoupper(trim($code));
    }

    public function recode(string $code): void
    {
        $code = self::normalizedCode($code);
        if (1 !== preg_match('/^[A-Z0-9]{1,8}$/', $code)) {
            throw new InvalidTypeCode($code);
        }

        $this->code = $code;
    }

    public function rename(string $name): void
    {
        $name = trim($name);
        if ('' === $name) {
            throw new EmptyTypeName();
        }

        $this->name = $name;
    }

    public function recolor(string $color): void
    {
        $color = strtolower(trim($color));
        if (1 !== preg_match('/^#[0-9a-f]{6}$/', $color)) {
            throw new InvalidTypeColor($color);
        }

        $this->color = $color;
    }

    public function id(): Ulid
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function code(): string
    {
        return $this->code;
    }

    public function color(): string
    {
        return $this->color;
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
    public function archivedVariants(): array
    {
        return $this->archivedVariants;
    }

    /**
     * @return list<string>
     */
    public function activeVariants(): array
    {
        return array_values(array_filter($this->variants, fn (string $variant): bool => !$this->isVariantArchived($variant)));
    }

    public function prefixesNames(): bool
    {
        return $this->prefixesNames;
    }

    public function workspace(): Workspace
    {
        return $this->workspace;
    }
}
