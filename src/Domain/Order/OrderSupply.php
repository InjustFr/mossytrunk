<?php

declare(strict_types=1);

namespace App\Domain\Order;

use App\Domain\Order\Exception\InvalidOrderQuantity;
use App\Domain\Product\SellableItem;
use App\Domain\Shared\Money;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

#[ORM\Entity]
#[ORM\Table(name: 'order_supply')]
#[ORM\Index(name: 'order_supply_product_idx', columns: ['product_id'])]
class OrderSupply
{
    #[ORM\Id]
    #[ORM\Column(type: UlidType::NAME, unique: true)]
    private Ulid $id;

    #[ORM\ManyToOne(targetEntity: Order::class, inversedBy: 'supplies')]
    #[ORM\JoinColumn(name: 'order_id', nullable: false, onDelete: 'CASCADE')]
    private Order $order;

    #[ORM\Column(type: UlidType::NAME)]
    private Ulid $productId;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $variant;

    #[ORM\Column(length: 255)]
    private string $label;

    #[ORM\Column]
    private int $quantity;

    #[ORM\Embedded(class: Money::class, columnPrefix: 'cost_')]
    private Money $cost;

    public function __construct(Order $order, Ulid $productId, SellableItem $item, int $quantity, Money $cost)
    {
        if ($quantity < 1) {
            throw new InvalidOrderQuantity();
        }

        $this->id = new Ulid();
        $this->order = $order;
        $this->productId = $productId;
        $this->variant = $item->variant;
        $this->label = $item->label();
        $this->quantity = $quantity;
        $this->cost = $cost;
    }

    public function uses(Ulid $productId, ?string $variant): bool
    {
        return $this->productId->equals($productId) && $this->variant === $variant;
    }

    public function add(int $quantity, Money $cost): void
    {
        if ($quantity < 1) {
            throw new InvalidOrderQuantity();
        }

        $this->quantity += $quantity;
        $this->cost = $this->cost->add($cost);
    }

    public function copyInto(Order $order): self
    {
        $copy = clone $this;
        $copy->id = new Ulid();
        $copy->order = $order;

        return $copy;
    }

    public function id(): Ulid
    {
        return $this->id;
    }

    public function productId(): Ulid
    {
        return $this->productId;
    }

    public function variant(): ?string
    {
        return $this->variant;
    }

    public function label(): string
    {
        return $this->label;
    }

    public function quantity(): int
    {
        return $this->quantity;
    }

    public function cost(): Money
    {
        return $this->cost;
    }
}
