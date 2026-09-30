<?php

declare(strict_types=1);

namespace App\Domain\Product;

use App\Domain\Identity\Workspace;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

/**
 * Kind of product (Print, Sticker, T-shirt…). Used to name products ("Print Forêt"), build their
 * references, filter the catalogue, edit products in batch and target discounts.
 * Name and code are unique (checked by the use cases).
 */
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

    /** Short uppercase code used as reference prefix, e.g. PRI for Print. Fixed at creation. */
    #[ORM\Column(length: 8)]
    private string $code;

    #[ORM\Column(length: 7)]
    private string $color;

    private function __construct(Ulid $id, Workspace $workspace, string $name, string $code, string $color)
    {
        $this->id = $id;
        $this->workspace = $workspace;
        $this->rename($name);
        $this->recolor($color);

        if (1 !== preg_match('/^[A-Z0-9]{1,8}$/', $code)) {
            throw InvalidProduct::invalidTypeCode($code);
        }
        $this->code = $code;
    }

    public static function create(Workspace $workspace, string $name, string $code, string $color = self::PALETTE[0]): self
    {
        return new self(new Ulid(), $workspace, $name, $code, $color);
    }

    public static function paletteColor(int $index): string
    {
        return self::PALETTE[$index % \count(self::PALETTE)];
    }

    /**
     * Code suggestion from a name: first three letters/digits, uppercase, accents removed ("Tote bag" → TOT).
     */
    public static function codeFor(string $name): string
    {
        $ascii = strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '', (string) iconv('UTF-8', 'ASCII//TRANSLIT', $name)));

        return '' === $ascii ? 'TYP' : substr($ascii, 0, 3);
    }

    public function rename(string $name): void
    {
        $name = trim($name);
        if ('' === $name) {
            throw InvalidProduct::emptyTypeName();
        }

        $this->name = $name;
    }

    public function recolor(string $color): void
    {
        $color = strtolower(trim($color));
        if (1 !== preg_match('/^#[0-9a-f]{6}$/', $color)) {
            throw InvalidProduct::invalidTypeColor($color);
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

    public function workspace(): Workspace
    {
        return $this->workspace;
    }
}
