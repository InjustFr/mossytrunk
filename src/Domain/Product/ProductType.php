<?php

declare(strict_types=1);

namespace App\Domain\Product;

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
class ProductType
{
    #[ORM\Id]
    #[ORM\Column(type: UlidType::NAME, unique: true)]
    private Ulid $id;

    #[ORM\Column(length: 100, unique: true)]
    private string $name;

    /** Short uppercase code used as reference prefix, e.g. PRI for Print. Fixed at creation. */
    #[ORM\Column(length: 8, unique: true)]
    private string $code;

    private function __construct(Ulid $id, string $name, string $code)
    {
        $this->id = $id;
        $this->rename($name);

        if (1 !== preg_match('/^[A-Z0-9]{1,8}$/', $code)) {
            throw InvalidProduct::invalidTypeCode($code);
        }
        $this->code = $code;
    }

    public static function create(string $name, string $code): self
    {
        return new self(new Ulid(), $name, $code);
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
}
