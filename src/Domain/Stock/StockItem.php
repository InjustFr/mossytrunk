<?php

declare(strict_types=1);

namespace App\Domain\Stock;

use App\Domain\Identity\Workspace;
use App\Domain\Product\Product;
use App\Domain\Shared\Exception\NegativeAmount;
use App\Domain\Shared\Money;
use App\Domain\Stock\Exception\NegativeCount;
use App\Domain\Stock\Exception\NonPositiveStockQuantity;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

#[ORM\Entity]
#[ORM\Table(name: 'stock_item')]
#[ORM\Index(name: 'stock_item_product_idx', columns: ['product_id'])]
class StockItem
{
    #[ORM\Id]
    #[ORM\Column(type: UlidType::NAME, unique: true)]
    private Ulid $id;

    #[ORM\ManyToOne(targetEntity: Workspace::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Workspace $workspace;

    #[ORM\ManyToOne(targetEntity: Product::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Product $product;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $variant;

    #[ORM\Column]
    private int $onHand = 0;

    #[ORM\Column(nullable: true)]
    private ?int $lastUnitCostCents = null;

    /** @var Collection<int, StockLot> */
    #[ORM\OneToMany(targetEntity: StockLot::class, mappedBy: 'item', cascade: ['persist'], orphanRemoval: true)]
    private Collection $lots;

    private function __construct(Product $product, ?string $variant)
    {
        $this->id = new Ulid();
        $this->workspace = $product->workspace();
        $this->product = $product;
        $this->variant = $variant;
        $this->lots = new ArrayCollection();
    }

    public static function open(Product $product, ?string $variant): self
    {
        return new self($product, $product->sellable($variant)->variant);
    }

    public function receive(int $quantity, Money $totalCost, LotOrigin $origin, \DateTimeImmutable $receivedAt, ?Ulid $sourceId = null): StockLot
    {
        if ($quantity < 1) {
            throw new NonPositiveStockQuantity();
        }
        if ($totalCost->isNegative()) {
            throw new NegativeAmount('paid_price');
        }

        $lot = new StockLot($this, $quantity, max(0, $quantity + min($this->onHand, 0)), $totalCost, $receivedAt, $origin, $sourceId);
        $this->lots->add($lot);
        $this->onHand += $quantity;
        if ($lot->isPurchase()) {
            $this->lastUnitCostCents = $lot->unitCost()->amount();
        }

        return $lot;
    }

    public function withdraw(int $quantity, Money $fallbackUnitCost): Money
    {
        if ($quantity < 1) {
            throw new NonPositiveStockQuantity();
        }

        $consumption = $this->consume($quantity);
        $missing = $quantity - $consumption->quantity;
        $this->onHand -= $quantity;

        return $consumption->cost->add($this->costWhenEmpty($fallbackUnitCost)->multiply($missing));
    }

    public function cancelWithdrawal(int $quantity): void
    {
        if ($quantity < 1) {
            throw new NonPositiveStockQuantity();
        }

        $this->onHand += $quantity;
        $this->rebalanceLots();
    }

    public function takeBack(int $quantity, Money $totalCost, \DateTimeImmutable $returnedAt, Ulid $orderId): void
    {
        $this->receive($quantity, $totalCost, LotOrigin::Return, $returnedAt, $orderId);
    }

    public function cancelReturnOf(Ulid $orderId): void
    {
        foreach ($this->lots() as $lot) {
            if ($lot->isReturnOf($orderId)) {
                $this->onHand -= $lot->quantity();
                $this->lots->removeElement($lot);
            }
        }
        $this->rebalanceLots();
    }

    public function correctTo(int $counted, Money $fallbackUnitCost, \DateTimeImmutable $countedAt): StockCorrection
    {
        if ($counted < 0) {
            throw new NegativeCount();
        }

        $expected = $this->onHand;
        if ($counted < $expected) {
            return new StockCorrection($expected, $counted, $this->withdraw($expected - $counted, $fallbackUnitCost));
        }
        if ($counted > $expected) {
            $found = $counted - $expected;
            $this->receive($found, $this->nextUnitCost($fallbackUnitCost)->multiply($found), LotOrigin::Correction, $countedAt);
        }

        return new StockCorrection($expected, $counted, Money::zero());
    }

    public function absorb(self $other): void
    {
        foreach ($other->lots() as $lot) {
            $this->lots->add(new StockLot($this, $lot->quantity(), $lot->remaining(), $lot->totalCost(), $lot->receivedAt(), $lot->origin(), $lot->sourceId()));
        }
        $this->onHand += $other->onHand;
        $this->lastUnitCostCents ??= $other->lastUnitCostCents;
        $this->rebalanceLots();
    }

    public function restate(Ulid $supplierOrderId, int $quantity, Money $totalCost, \DateTimeImmutable $receivedAt): ?StockLot
    {
        $previous = $this->lotFrom($supplierOrderId);
        if (null === $previous) {
            return $quantity > 0 ? $this->receive($quantity, $totalCost, LotOrigin::SupplierOrder, $receivedAt, $supplierOrderId) : null;
        }
        if ($quantity < 0) {
            throw new NonPositiveStockQuantity();
        }
        if ($totalCost->isNegative()) {
            throw new NegativeAmount('paid_price');
        }

        $delta = $quantity - $previous->quantity();
        $this->lots->removeElement($previous);
        $this->onHand += $delta;
        $lot = null;
        if ($quantity > 0) {
            $lot = new StockLot($this, $quantity, max(0, min($quantity, $previous->remaining() + $delta)), $totalCost, $receivedAt, LotOrigin::SupplierOrder, $supplierOrderId);
            $this->lots->add($lot);
        }
        $this->rebalanceLots();
        $this->lastUnitCostCents = $this->latestPurchase()?->unitCost()->amount() ?? $this->lastUnitCostCents;

        return $lot;
    }

    public function moveLotsOf(Ulid $fromSupplierOrderId, Ulid $toSupplierOrderId): void
    {
        $moved = $this->lotFrom($fromSupplierOrderId);
        if (null === $moved) {
            return;
        }
        $kept = $this->lotFrom($toSupplierOrderId);
        $this->lots->removeElement($moved);
        if (null !== $kept) {
            $this->lots->removeElement($kept);
        }
        $this->lots->add(new StockLot(
            $this,
            $moved->quantity() + ($kept?->quantity() ?? 0),
            $moved->remaining() + ($kept?->remaining() ?? 0),
            $moved->totalCost()->add($kept?->totalCost() ?? Money::zero()),
            null === $kept ? $moved->receivedAt() : min($moved->receivedAt(), $kept->receivedAt()),
            LotOrigin::SupplierOrder,
            $toSupplierOrderId,
        ));
    }

    public function firstPurchaseUnitCost(): ?Money
    {
        return array_find($this->lots(), static fn (StockLot $lot): bool => $lot->isPurchase())?->unitCost();
    }

    public function isLatestPurchase(StockLot $lot): bool
    {
        return $this->latestPurchase() === $lot;
    }

    private function latestPurchase(): ?StockLot
    {
        return array_find(array_reverse($this->lots()), static fn (StockLot $lot): bool => $lot->isPurchase());
    }

    private function lotFrom(Ulid $supplierOrderId): ?StockLot
    {
        return $this->lots->findFirst(static fn (int $key, StockLot $lot): bool => $lot->isFrom($supplierOrderId));
    }

    public function nextUnitCost(Money $fallbackUnitCost): Money
    {
        return array_find($this->lots(), static fn (StockLot $lot): bool => !$lot->isExhausted())?->unitCost() ?? $this->costWhenEmpty($fallbackUnitCost);
    }

    public function remainingValue(): Money
    {
        return Money::sum(array_map(static fn (StockLot $lot): Money => $lot->remainingValue(), $this->lots()));
    }

    public function isLowAt(int $threshold): bool
    {
        return $this->onHand <= $threshold;
    }

    public function isNegative(): bool
    {
        return $this->onHand < 0;
    }

    public function isFor(Ulid $productId, ?string $variant): bool
    {
        return $this->product->id()->equals($productId) && $this->variant === $variant;
    }

    /**
     * @return list<StockLot>
     */
    public function lots(): array
    {
        $lots = array_values($this->lots->toArray());
        usort($lots, static fn (StockLot $a, StockLot $b): int => $a->comesBefore($b) ? -1 : 1);

        return $lots;
    }

    private function consume(int $quantity): LotConsumption
    {
        $taken = 0;
        $cost = Money::zero();
        foreach ($this->lots() as $lot) {
            if ($taken >= $quantity) {
                break;
            }
            $consumption = $lot->take($quantity - $taken);
            $taken += $consumption->quantity;
            $cost = $cost->add($consumption->cost);
        }

        return new LotConsumption($taken, $cost);
    }

    private function rebalanceLots(): void
    {
        $surplus = $this->remainingUnits() - max($this->onHand, 0);
        if ($surplus > 0) {
            $this->consume($surplus);
        }
        if ($surplus < 0) {
            $this->refill(-$surplus);
        }
    }

    private function refill(int $quantity): void
    {
        foreach (array_reverse($this->lots()) as $lot) {
            if ($quantity <= 0) {
                return;
            }
            $quantity -= $lot->refill($quantity);
        }
    }

    private function remainingUnits(): int
    {
        return array_sum(array_map(static fn (StockLot $lot): int => $lot->remaining(), $this->lots()));
    }

    private function costWhenEmpty(Money $fallbackUnitCost): Money
    {
        return null === $this->lastUnitCostCents ? $fallbackUnitCost : Money::cents($this->lastUnitCostCents);
    }

    public function id(): Ulid
    {
        return $this->id;
    }

    public function product(): Product
    {
        return $this->product;
    }

    public function variant(): ?string
    {
        return $this->variant;
    }

    public function onHand(): int
    {
        return $this->onHand;
    }

    public function workspace(): Workspace
    {
        return $this->workspace;
    }
}
