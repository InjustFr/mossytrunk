<?php

declare(strict_types=1);

namespace App\Domain\Purchasing;

use App\Domain\Purchasing\Exception\NegativeReceivedQuantity;
use App\Domain\Purchasing\Exception\NonPositiveOrderedQuantity;
use App\Domain\Shared\Exception\NegativeAmount;
use App\Domain\Shared\Money;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

#[ORM\Entity]
#[ORM\Table(name: 'supplier_order_line')]
class SupplierOrderLine
{
    #[ORM\Id]
    #[ORM\Column(type: UlidType::NAME, unique: true)]
    private Ulid $id;

    #[ORM\ManyToOne(targetEntity: SupplierOrder::class, inversedBy: 'lines')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private SupplierOrder $order;

    #[ORM\Column(type: UlidType::NAME)]
    private Ulid $productId;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $variant;

    #[ORM\Column(length: 255)]
    private string $label;

    #[ORM\Column]
    private int $orderedQuantity;

    #[ORM\Embedded(class: Money::class, columnPrefix: 'total_price_')]
    private Money $totalPrice;

    #[ORM\Embedded(class: Money::class, columnPrefix: 'discount_share_')]
    private Money $discountShare;

    #[ORM\Embedded(class: Money::class, columnPrefix: 'fees_share_')]
    private Money $feesShare;

    #[ORM\Column(nullable: true)]
    private ?int $receivedQuantity = null;

    #[ORM\Column]
    private int $position;

    public function __construct(SupplierOrder $order, PurchasedItem $purchased, int $position)
    {
        if ($purchased->quantity < 1) {
            throw new NonPositiveOrderedQuantity();
        }
        if ($purchased->totalPrice->isNegative()) {
            throw new NegativeAmount('paid_price');
        }

        $this->id = new Ulid();
        $this->order = $order;
        $this->productId = $purchased->productId;
        $this->variant = $purchased->item->variant;
        $this->label = $purchased->item->label();
        $this->orderedQuantity = $purchased->quantity;
        $this->totalPrice = $purchased->totalPrice;
        $this->discountShare = Money::zero();
        $this->feesShare = Money::zero();
        $this->position = $position;
    }

    public function share(Money $discount, Money $fees): void
    {
        $this->discountShare = $discount;
        $this->feesShare = $fees;
    }

    public function landedCost(): Money
    {
        return $this->totalPrice->subtract($this->discountShare)->add($this->feesShare);
    }

    public function receive(int $quantity): void
    {
        if ($quantity < 0) {
            throw new NegativeReceivedQuantity($this->label);
        }

        $this->receivedQuantity = $quantity;
    }

    public function absorb(self $other): void
    {
        $this->orderedQuantity += $other->orderedQuantity;
        $this->totalPrice = $this->totalPrice->add($other->totalPrice);
        $this->receivedQuantity = null === $this->receivedQuantity && null === $other->receivedQuantity ? null : (int) $this->receivedQuantity + (int) $other->receivedQuantity;
    }

    public function copyInto(SupplierOrder $order, int $position): self
    {
        $copy = clone $this;
        $copy->id = new Ulid();
        $copy->order = $order;
        $copy->position = $position;

        return $copy;
    }

    public function isFor(Ulid $productId, ?string $variant): bool
    {
        return $this->productId->equals($productId) && $this->variant === $variant;
    }

    public function plannedUnitCost(): Money
    {
        return $this->landedCost()->prorate(1, $this->orderedQuantity);
    }

    public function unitCost(): ?Money
    {
        return null === $this->receivedQuantity || 0 === $this->receivedQuantity ? null : $this->landedCost()->prorate(1, $this->receivedQuantity);
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

    public function orderedQuantity(): int
    {
        return $this->orderedQuantity;
    }

    public function totalPrice(): Money
    {
        return $this->totalPrice;
    }

    public function discountShare(): Money
    {
        return $this->discountShare;
    }

    public function feesShare(): Money
    {
        return $this->feesShare;
    }

    public function receivedQuantity(): ?int
    {
        return $this->receivedQuantity;
    }

    public function position(): int
    {
        return $this->position;
    }
}
