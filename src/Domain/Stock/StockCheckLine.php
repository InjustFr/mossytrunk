<?php

declare(strict_types=1);

namespace App\Domain\Stock;

use App\Domain\Shared\Money;
use App\Domain\Stock\Exception\NothingToResolve;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

#[ORM\Entity]
#[ORM\Table(name: 'stock_check_line')]
class StockCheckLine
{
    #[ORM\Id]
    #[ORM\Column(type: UlidType::NAME, unique: true)]
    private Ulid $id;

    #[ORM\ManyToOne(targetEntity: StockCheck::class, inversedBy: 'lines')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private StockCheck $check;

    #[ORM\Column(type: UlidType::NAME)]
    private Ulid $productId;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $variant;

    #[ORM\Column(length: 255)]
    private string $label;

    #[ORM\Column]
    private int $expected;

    #[ORM\Column]
    private int $counted;

    #[ORM\Embedded(class: Money::class, columnPrefix: 'loss_cost_')]
    private Money $lossCost;

    #[ORM\Column]
    private int $unexplained;

    #[ORM\Column]
    private bool $dismissed = false;

    public function __construct(StockCheck $check, StockItem $item, string $label, StockCorrection $correction)
    {
        $this->id = new Ulid();
        $this->check = $check;
        $this->productId = $item->product()->id();
        $this->variant = $item->variant();
        $this->label = $label;
        $this->expected = $correction->expected;
        $this->counted = $correction->counted;
        $this->lossCost = $correction->lossCost;
        $this->unexplained = $correction->missing();
    }

    public function explain(int $wanted): LotConsumption
    {
        $taken = min($wanted, $this->unexplained);
        $explainedBefore = $this->missing() - $this->unexplained;
        $this->unexplained -= $taken;

        return new LotConsumption($taken, $this->lossOfFirst($explainedBefore + $taken)->subtract($this->lossOfFirst($explainedBefore)));
    }

    public function dismiss(): void
    {
        if (0 === $this->unexplained) {
            throw new NothingToResolve();
        }

        $this->unexplained = 0;
        $this->dismissed = true;
    }

    public function isFor(Ulid $productId, ?string $variant): bool
    {
        return $this->productId->equals($productId) && $this->variant === $variant;
    }

    public function missing(): int
    {
        return max(0, $this->expected - $this->counted);
    }

    public function surplus(): int
    {
        return max(0, $this->counted - $this->expected);
    }

    private function lossOfFirst(int $units): Money
    {
        return 0 === $this->missing() ? Money::zero() : Money::cents((int) round($this->lossCost->amount() * $units / $this->missing(), 0, \PHP_ROUND_HALF_UP));
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

    public function expected(): int
    {
        return $this->expected;
    }

    public function counted(): int
    {
        return $this->counted;
    }

    public function lossCost(): Money
    {
        return $this->lossCost;
    }

    public function unexplained(): int
    {
        return $this->unexplained;
    }

    public function isDismissed(): bool
    {
        return $this->dismissed;
    }
}
