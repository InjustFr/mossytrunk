<?php

declare(strict_types=1);

namespace App\Domain\Purchasing;

use App\Domain\Shared\InvalidMoney;
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

    #[ORM\Column(nullable: true)]
    private ?int $receivedQuantity = null;

    #[ORM\Column]
    private int $position;

    public function __construct(SupplierOrder $order, PurchasedItem $purchased, int $position)
    {
        if ($purchased->quantity < 1) {
            throw InvalidPurchase::quantityMustBePositive();
        }
        if ($purchased->totalPrice->isNegative()) {
            throw InvalidMoney::mustNotBeNegative('Le prix payé');
        }

        $this->id = new Ulid();
        $this->order = $order;
        $this->productId = $purchased->item->productId;
        $this->variant = $purchased->item->variant;
        $this->label = $purchased->item->label();
        $this->orderedQuantity = $purchased->quantity;
        $this->totalPrice = $purchased->totalPrice;
        $this->position = $position;
    }

    public function receive(int $quantity): void
    {
        if ($quantity < 0) {
            throw InvalidPurchase::receivedQuantityNegative($this->label);
        }

        $this->receivedQuantity = $quantity;
    }

    public function isFor(Ulid $productId, ?string $variant): bool
    {
        return $this->productId->equals($productId) && $this->variant === $variant;
    }

    public function plannedUnitCost(): Money
    {
        return self::divide($this->totalPrice, $this->orderedQuantity);
    }

    public function unitCost(): ?Money
    {
        return null === $this->receivedQuantity || 0 === $this->receivedQuantity ? null : self::divide($this->totalPrice, $this->receivedQuantity);
    }

    private static function divide(Money $total, int $quantity): Money
    {
        return Money::cents((int) round($total->amount() / $quantity, 0, \PHP_ROUND_HALF_UP));
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

    public function receivedQuantity(): ?int
    {
        return $this->receivedQuantity;
    }

    public function position(): int
    {
        return $this->position;
    }
}
