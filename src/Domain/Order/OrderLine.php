<?php

declare(strict_types=1);

namespace App\Domain\Order;

use App\Domain\Order\Exception\InvalidOrderQuantity;
use App\Domain\Order\Exception\LineAlreadyIdentified;
use App\Domain\Order\Exception\UnknownProductChosen;
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

    #[ORM\Column(type: UlidType::NAME, nullable: true)]
    private ?Ulid $productId;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $variant;

    #[ORM\Column(length: 255)]
    private string $productName;

    #[ORM\Embedded(class: Money::class, columnPrefix: 'unit_price_')]
    private Money $unitPrice;

    #[ORM\Embedded(class: Money::class, columnPrefix: 'cost_')]
    private Money $cost;

    #[ORM\Column]
    private int $quantity;

    /**
     * @internal built by Order
     */
    public function __construct(Order $order, SellableItem $item, int $quantity, Money $cost)
    {
        if ($quantity < 1) {
            throw new InvalidOrderQuantity();
        }

        $this->id = new Ulid();
        $this->order = $order;
        $this->productId = $item->productId;
        $this->variant = $item->variant;
        $this->productName = $item->productName;
        $this->unitPrice = $item->sellingPrice;
        $this->cost = $cost;
        $this->quantity = $quantity;
    }

    public function sells(SellableItem $item): bool
    {
        return null !== $this->productId && null !== $item->productId && $this->productId->equals($item->productId) && $this->variant === $item->variant;
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

    public function identify(SellableItem $item, Money $cost): void
    {
        if (!$this->sellsUnknownProduct()) {
            throw new LineAlreadyIdentified($this->label());
        }
        if (!$item->isKnown()) {
            throw new UnknownProductChosen();
        }

        $this->productId = $item->productId;
        $this->variant = $item->variant;
        $this->productName = $item->productName;
        $this->cost = $cost;
    }

    public function sameUnitAmountsAs(self $other): bool
    {
        return $this->unitPrice->equals($other->unitPrice);
    }

    public function add(int $quantity, Money $cost): void
    {
        if ($quantity < 1) {
            throw new InvalidOrderQuantity();
        }

        $this->quantity += $quantity;
        $this->cost = $this->cost->add($cost);
    }

    public function total(): Money
    {
        return $this->unitPrice->multiply($this->quantity);
    }

    public function cost(): Money
    {
        return $this->cost;
    }

    public function id(): Ulid
    {
        return $this->id;
    }

    public function productId(): ?Ulid
    {
        return $this->productId;
    }

    public function sellsUnknownProduct(): bool
    {
        return null === $this->productId;
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
        return Money::cents((int) round($this->cost->amount() / $this->quantity, 0, \PHP_ROUND_HALF_UP));
    }

    public function quantity(): int
    {
        return $this->quantity;
    }
}
