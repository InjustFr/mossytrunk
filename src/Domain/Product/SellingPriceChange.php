<?php

declare(strict_types=1);

namespace App\Domain\Product;

use App\Domain\Shared\Money;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

#[ORM\Entity]
#[ORM\Table(name: 'product_selling_price')]
class SellingPriceChange
{
    #[ORM\Id]
    #[ORM\Column(type: UlidType::NAME, unique: true)]
    private Ulid $id;

    #[ORM\ManyToOne(targetEntity: Product::class, inversedBy: 'priceHistory')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Product $product;

    #[ORM\Embedded(class: Money::class, columnPrefix: 'price_')]
    private Money $price;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    private \DateTimeImmutable $since;

    public function __construct(Product $product, Money $price, \DateTimeImmutable $since)
    {
        $this->id = new Ulid();
        $this->product = $product;
        $this->price = $price;
        $this->since = $since;
    }

    public function amend(Money $price, \DateTimeImmutable $since): void
    {
        $this->price = $price;
        $this->since = $since;
    }

    public function id(): Ulid
    {
        return $this->id;
    }

    public function price(): Money
    {
        return $this->price;
    }

    public function since(): \DateTimeImmutable
    {
        return $this->since;
    }
}
