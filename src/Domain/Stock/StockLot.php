<?php

declare(strict_types=1);

namespace App\Domain\Stock;

use App\Domain\Shared\Money;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

#[ORM\Entity]
#[ORM\Table(name: 'stock_lot')]
class StockLot
{
    #[ORM\Id]
    #[ORM\Column(type: UlidType::NAME, unique: true)]
    private Ulid $id;

    #[ORM\ManyToOne(targetEntity: StockItem::class, inversedBy: 'lots')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private StockItem $item;

    #[ORM\Column]
    private int $quantity;

    #[ORM\Column]
    private int $remaining;

    #[ORM\Embedded(class: Money::class, columnPrefix: 'total_cost_')]
    private Money $totalCost;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    private \DateTimeImmutable $receivedAt;

    #[ORM\Column(length: 16, enumType: LotOrigin::class)]
    private LotOrigin $origin;

    #[ORM\Column(type: UlidType::NAME, nullable: true)]
    private ?Ulid $sourceId;

    public function __construct(StockItem $item, int $quantity, int $remaining, Money $totalCost, \DateTimeImmutable $receivedAt, LotOrigin $origin, ?Ulid $sourceId)
    {
        $this->id = new Ulid();
        $this->item = $item;
        $this->quantity = $quantity;
        $this->remaining = $remaining;
        $this->totalCost = $totalCost;
        $this->receivedAt = $receivedAt;
        $this->origin = $origin;
        $this->sourceId = $sourceId;
    }

    public function take(int $wanted): LotConsumption
    {
        $taken = min($wanted, $this->remaining);
        $consumedBefore = $this->quantity - $this->remaining;
        $this->remaining -= $taken;

        return new LotConsumption($taken, $this->costOfFirst($consumedBefore + $taken)->subtract($this->costOfFirst($consumedBefore)));
    }

    public function refill(int $wanted): int
    {
        $given = min($wanted, $this->quantity - $this->remaining);
        $this->remaining += $given;

        return $given;
    }

    public function isExhausted(): bool
    {
        return 0 === $this->remaining;
    }

    public function remainingValue(): Money
    {
        return $this->totalCost->subtract($this->costOfFirst($this->quantity - $this->remaining));
    }

    public function unitCost(): Money
    {
        return $this->costOfFirst(1);
    }

    public function comesBefore(self $other): bool
    {
        return $this->receivedAt == $other->receivedAt
            ? (string) $this->id < (string) $other->id
            : $this->receivedAt < $other->receivedAt;
    }

    private function costOfFirst(int $units): Money
    {
        return Money::cents((int) round($this->totalCost->amount() * $units / $this->quantity, 0, \PHP_ROUND_HALF_UP));
    }

    public function id(): Ulid
    {
        return $this->id;
    }

    public function quantity(): int
    {
        return $this->quantity;
    }

    public function remaining(): int
    {
        return $this->remaining;
    }

    public function totalCost(): Money
    {
        return $this->totalCost;
    }

    public function receivedAt(): \DateTimeImmutable
    {
        return $this->receivedAt;
    }

    public function isReturnOf(Ulid $orderId): bool
    {
        return LotOrigin::Return === $this->origin && null !== $this->sourceId && $this->sourceId->equals($orderId);
    }

    public function origin(): LotOrigin
    {
        return $this->origin;
    }

    public function sourceId(): ?Ulid
    {
        return $this->sourceId;
    }
}
