<?php

declare(strict_types=1);

namespace App\Domain\Order;

use App\Domain\Product\SellableItem;
use App\Domain\Shared\Money;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

/**
 * `quantity` units of a (product, variant) tuple. Name and prices are snapshots taken when the
 * order is placed: later product changes never alter an order.
 */
#[ORM\Entity]
#[ORM\Table(name: 'order_line')]
class OrderLine
{
    #[ORM\Id]
    #[ORM\Column(type: UlidType::NAME, unique: true)]
    private Ulid $id;

    #[ORM\ManyToOne(targetEntity: Order::class, inversedBy: 'lines')]
    #[ORM\JoinColumn(name: 'order_id', nullable: false, onDelete: 'CASCADE')]
    private Order $order;

    #[ORM\Column(type: UlidType::NAME)]
    private Ulid $productId;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $variant;

    #[ORM\Column(length: 255)]
    private string $productName;

    #[ORM\Embedded(class: Money::class, columnPrefix: 'unit_price_')]
    private Money $unitPrice;

    #[ORM\Embedded(class: Money::class, columnPrefix: 'unit_cost_')]
    private Money $unitCost;

    #[ORM\Column]
    private int $quantity;

    /**
     * @internal built by Order
     */
    public function __construct(Order $order, SellableItem $item, int $quantity)
    {
        if ($quantity < 1) {
            throw InvalidOrder::invalidQuantity();
        }

        $this->id = new Ulid();
        $this->order = $order;
        $this->productId = $item->productId;
        $this->variant = $item->variant;
        $this->productName = $item->productName;
        $this->unitPrice = $item->sellingPrice;
        $this->unitCost = $item->buyingPrice;
        $this->quantity = $quantity;
    }

    public function sells(SellableItem $item): bool
    {
        return $this->productId->equals($item->productId) && $this->variant === $item->variant;
    }

    /**
     * @internal the order moves its sales from one product to another
     */
    public function reassign(SellableItem $item): void
    {
        $this->productId = $item->productId;
        $this->variant = $item->variant;
        $this->productName = $item->productName;
    }

    public function sameUnitAmountsAs(self $other): bool
    {
        return $this->unitPrice->equals($other->unitPrice) && $this->unitCost->equals($other->unitCost);
    }

    public function add(int $quantity): void
    {
        if ($quantity < 1) {
            throw InvalidOrder::invalidQuantity();
        }

        $this->quantity += $quantity;
    }

    public function total(): Money
    {
        return $this->unitPrice->multiply($this->quantity);
    }

    public function cost(): Money
    {
        return $this->unitCost->multiply($this->quantity);
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

    public function productName(): string
    {
        return $this->productName;
    }

    public function label(): string
    {
        return null === $this->variant ? $this->productName : \sprintf('%s — %s', $this->productName, $this->variant);
    }

    public function unitPrice(): Money
    {
        return $this->unitPrice;
    }

    public function unitCost(): Money
    {
        return $this->unitCost;
    }

    public function quantity(): int
    {
        return $this->quantity;
    }
}
