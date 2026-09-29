<?php

declare(strict_types=1);

namespace App\Domain\Stock;

use App\Domain\Event\Event;
use App\Domain\Identity\Workspace;
use App\Domain\Shared\Money;
use App\Domain\Shared\NotFound;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

#[ORM\Entity]
#[ORM\Table(name: 'stock_check')]
class StockCheck
{
    #[ORM\Id]
    #[ORM\Column(type: UlidType::NAME, unique: true)]
    private Ulid $id;

    #[ORM\ManyToOne(targetEntity: Workspace::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Workspace $workspace;

    #[ORM\ManyToOne(targetEntity: Event::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Event $event;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    private \DateTimeImmutable $checkedAt;

    /** @var Collection<int, StockCheckLine> */
    #[ORM\OneToMany(targetEntity: StockCheckLine::class, mappedBy: 'check', cascade: ['persist'], orphanRemoval: true)]
    private Collection $lines;

    /**
     * @param list<StockCount> $counts
     */
    private function __construct(Event $event, \DateTimeImmutable $checkedAt, array $counts)
    {
        if ([] === $counts) {
            throw InvalidStock::emptyCheck();
        }

        $this->id = new Ulid();
        $this->workspace = $event->workspace();
        $this->event = $event;
        $this->checkedAt = $checkedAt;
        $this->lines = new ArrayCollection();

        $counted = [];
        foreach ($counts as $count) {
            $key = $count->item->id()->toRfc4122();
            $label = $count->item->product()->sellable($count->item->variant())->label();
            if (isset($counted[$key])) {
                throw InvalidStock::countedTwice($label);
            }
            $counted[$key] = true;
            $correction = $count->item->correctTo($count->counted, $count->fallbackUnitCost, $checkedAt);
            $this->lines->add(new StockCheckLine($this, $count->item, $label, $correction));
        }
    }

    /**
     * @param list<StockCount> $counts
     */
    public static function take(Event $event, \DateTimeImmutable $checkedAt, array $counts): self
    {
        return new self($event, $checkedAt, $counts);
    }

    public function explain(Ulid $productId, ?string $variant, int $quantity): LotConsumption
    {
        $taken = 0;
        $cost = Money::zero();
        foreach ($this->lines as $line) {
            if ($taken < $quantity && $line->isFor($productId, $variant)) {
                $consumption = $line->explain($quantity - $taken);
                $taken += $consumption->quantity;
                $cost = $cost->add($consumption->cost);
            }
        }

        return new LotConsumption($taken, $cost);
    }

    public function dismiss(Ulid $lineId): void
    {
        foreach ($this->lines as $line) {
            if ($line->id()->equals($lineId)) {
                $line->dismiss();

                return;
            }
        }

        throw NotFound::entity('Ligne d\'inventaire', (string) $lineId);
    }

    public function unexplainedUnits(): int
    {
        return array_sum(array_map(static fn (StockCheckLine $line): int => $line->unexplained(), $this->lines()));
    }

    public function id(): Ulid
    {
        return $this->id;
    }

    public function event(): Event
    {
        return $this->event;
    }

    public function checkedAt(): \DateTimeImmutable
    {
        return $this->checkedAt;
    }

    /**
     * @return list<StockCheckLine>
     */
    public function lines(): array
    {
        return array_values($this->lines->toArray());
    }

    public function workspace(): Workspace
    {
        return $this->workspace;
    }
}
