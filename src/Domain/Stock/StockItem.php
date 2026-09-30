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
        if (LotOrigin::Purchase === $origin || LotOrigin::SupplierOrder === $origin) {
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

    public function putBack(int $quantity, Money $totalCost, \DateTimeImmutable $soldAt): void
    {
        $this->receive($quantity, $totalCost, LotOrigin::Return, $soldAt);
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
        $this->consume($this->remainingUnits() - max($this->onHand, 0));
    }

    public function nextUnitCost(Money $fallbackUnitCost): Money
    {
        foreach ($this->lots() as $lot) {
            if (!$lot->isExhausted()) {
                return $lot->unitCost();
            }
        }

        return $this->costWhenEmpty($fallbackUnitCost);
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
     * @return list<StockLot> oldest first
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
