<?php

declare(strict_types=1);

namespace App\Domain\Order;

use App\Domain\Discount\AppliedDiscount;
use App\Domain\Event\Event;
use App\Domain\Shared\Money;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

/**
 * A sale made during an event.
 *
 * Rules (see docs/business/orders.md):
 * - always attached to an event, and placed at a moment covered by the event's period;
 * - contains at least one line; identical (product, variant) tuples are merged;
 * - product names and prices, and applied discounts, are snapshots;
 * - discounts never exceed the subtotal;
 * - an order imported from SumUp keeps its transaction code (unique → no duplicate import).
 */
#[ORM\Entity]
#[ORM\Table(name: '`order`')]
#[ORM\Index(name: 'order_placed_at_idx', columns: ['placed_at'])]
class Order
{
    #[ORM\Id]
    #[ORM\Column(type: UlidType::NAME, unique: true)]
    private Ulid $id;

    #[ORM\Column(length: 64, unique: true)]
    private string $reference;

    #[ORM\ManyToOne(targetEntity: Event::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Event $event;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    private \DateTimeImmutable $placedAt;

    /** @var Collection<int, OrderLine> */
    #[ORM\OneToMany(targetEntity: OrderLine::class, mappedBy: 'order', cascade: ['persist'], orphanRemoval: true)]
    private Collection $lines;

    /** @var list<array{label: string, amount: int}> */
    #[ORM\Column(type: Types::JSON)]
    private array $appliedDiscounts = [];

    #[ORM\Column(length: 16, enumType: OrderSource::class)]
    private OrderSource $source;

    #[ORM\Column(length: 64, unique: true, nullable: true)]
    private ?string $sumUpTransactionCode = null;

    /**
     * @param list<OrderedItem>     $items
     * @param list<AppliedDiscount> $discounts
     */
    private function __construct(string $reference, Event $event, \DateTimeImmutable $placedAt, array $items, array $discounts, OrderSource $source)
    {
        if (!$event->covers($placedAt)) {
            throw InvalidOrder::outsideEvent($event->name(), $placedAt);
        }
        if ([] === $items) {
            throw InvalidOrder::empty();
        }

        $this->id = new Ulid();
        $this->reference = $reference;
        $this->event = $event;
        $this->placedAt = $placedAt;
        $this->source = $source;
        $this->lines = new ArrayCollection();

        foreach ($items as $item) {
            $this->addItem($item);
        }

        $this->applyDiscounts($discounts);
    }

    /**
     * @param list<OrderedItem>     $items
     * @param list<AppliedDiscount> $discounts computed by the DiscountCalculator
     */
    public static function place(Event $event, \DateTimeImmutable $placedAt, array $items, array $discounts): self
    {
        return new self(self::generateReference($placedAt), $event, $placedAt, $items, $discounts, OrderSource::Manual);
    }

    /**
     * @param list<OrderedItem> $items
     * @param Money             $amountPaid what SumUp actually charged; any gap with the subtotal is SumUp's own discount
     */
    public static function importFromSumUp(string $transactionCode, Event $event, \DateTimeImmutable $placedAt, array $items, Money $amountPaid): self
    {
        $order = new self($transactionCode, $event, $placedAt, $items, [], OrderSource::SumUp);
        $order->sumUpTransactionCode = $transactionCode;

        $gap = $order->subtotal()->subtract($amountPaid);
        if ($gap->isPositive()) {
            $order->applyDiscounts([new AppliedDiscount('Remise SumUp', $gap)]);
        }

        return $order;
    }

    public function subtotal(): Money
    {
        return Money::sum($this->lines->map(static fn (OrderLine $line): Money => $line->total()));
    }

    public function discountTotal(): Money
    {
        return Money::sum(array_map(static fn (AppliedDiscount $discount): Money => $discount->amount, $this->appliedDiscounts()));
    }

    public function total(): Money
    {
        return $this->subtotal()->subtract($this->discountTotal());
    }

    /**
     * What the sold units cost the business (buying prices at the time of sale).
     */
    public function costOfGoods(): Money
    {
        return Money::sum($this->lines->map(static fn (OrderLine $line): Money => $line->cost()));
    }

    public function id(): Ulid
    {
        return $this->id;
    }

    public function reference(): string
    {
        return $this->reference;
    }

    public function event(): Event
    {
        return $this->event;
    }

    public function placedAt(): \DateTimeImmutable
    {
        return $this->placedAt;
    }

    public function source(): OrderSource
    {
        return $this->source;
    }

    public function sumUpTransactionCode(): ?string
    {
        return $this->sumUpTransactionCode;
    }

    /**
     * @return list<OrderLine>
     */
    public function lines(): array
    {
        return array_values($this->lines->toArray());
    }

    public function itemCount(): int
    {
        return array_sum($this->lines->map(static fn (OrderLine $line): int => $line->quantity())->toArray());
    }

    /**
     * @return list<AppliedDiscount>
     */
    public function appliedDiscounts(): array
    {
        return array_map(AppliedDiscount::fromArray(...), $this->appliedDiscounts);
    }

    private function addItem(OrderedItem $ordered): void
    {
        foreach ($this->lines as $line) {
            if ($line->sells($ordered->item)) {
                $line->add($ordered->quantity);

                return;
            }
        }

        $this->lines->add(new OrderLine($this, $ordered->item, $ordered->quantity));
    }

    /**
     * @param list<AppliedDiscount> $discounts
     */
    private function applyDiscounts(array $discounts): void
    {
        $total = Money::sum(array_map(static fn (AppliedDiscount $discount): Money => $discount->amount, $discounts));
        if ($total->greaterThan($this->subtotal())) {
            throw InvalidOrder::discountExceedsSubtotal();
        }

        $this->appliedDiscounts = array_map(static fn (AppliedDiscount $discount): array => $discount->toArray(), $discounts);
    }

    /**
     * CMD-YYYYMMDD-XXXXXX: readable, sortable by day, random suffix from a ULID.
     */
    private static function generateReference(\DateTimeImmutable $placedAt): string
    {
        return \sprintf('CMD-%s-%s', $placedAt->format('Ymd'), substr((string) new Ulid(), -6));
    }
}
