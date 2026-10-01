<?php

declare(strict_types=1);

namespace App\Domain\Order;

use App\Domain\Discount\AppliedDiscount;
use App\Domain\Event\Event;
use App\Domain\Identity\Workspace;
use App\Domain\Order\Exception\DiscountExceedsSubtotal;
use App\Domain\Order\Exception\EmptyOrder;
use App\Domain\Order\Exception\NegativeShippingCost;
use App\Domain\Order\Exception\OrderAlreadyRefunded;
use App\Domain\Order\Exception\OrderOutsideEvent;
use App\Domain\Order\Exception\OrdersNotMergeable;
use App\Domain\Order\Exception\RefundedOrderLocked;
use App\Domain\Product\SellableItem;
use App\Domain\Shared\DateRange;
use App\Domain\Shared\Exception\NotFound;
use App\Domain\Shared\Money;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

#[ORM\Entity]
#[ORM\Table(name: '`order`')]
#[ORM\Index(name: 'order_workspace_placed_at_idx', columns: ['workspace_id', 'placed_at'])]
#[ORM\UniqueConstraint(name: 'order_workspace_reference', columns: ['workspace_id', 'reference'])]
class Order
{
    public const string MANUAL = 'manual';

    private const int IMPORT_ROUNDING_TOLERANCE_CENTS = 2;

    #[ORM\Id]
    #[ORM\Column(type: UlidType::NAME, unique: true)]
    private Ulid $id;

    #[ORM\Column(length: 64)]
    private string $reference;

    #[ORM\ManyToOne(targetEntity: Workspace::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Workspace $workspace;

    #[ORM\ManyToOne(targetEntity: Event::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Event $event;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    private \DateTimeImmutable $placedAt;

    /** @var Collection<int, OrderLine> */
    #[ORM\OneToMany(targetEntity: OrderLine::class, mappedBy: 'order', cascade: ['persist'], orphanRemoval: true)]
    private Collection $lines;

    /** @var list<array{label: string, amount: int}> */
    #[ORM\Column(type: Types::JSON)]
    private array $appliedDiscounts = [];

    #[ORM\Column(length: 32)]
    private string $source;

    /** @var Collection<int, ImportedSale> */
    #[ORM\OneToMany(targetEntity: ImportedSale::class, mappedBy: 'order', cascade: ['persist'])]
    private Collection $importedSales;

    #[ORM\Column(length: 16, nullable: true, enumType: PaymentMethod::class)]
    private ?PaymentMethod $paymentMethod = null;

    #[ORM\Embedded(class: Money::class, columnPrefix: 'shipping_')]
    private Money $shipping;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $refundedAt = null;

    /**
     * @param list<OrderedItem>     $items
     * @param list<AppliedDiscount> $discounts
     */
    private function __construct(string $reference, Workspace $workspace, ?Event $event, \DateTimeImmutable $placedAt, array $items, array $discounts, string $source)
    {
        if (null !== $event && !$event->covers($placedAt)) {
            throw new OrderOutsideEvent($event->name(), $placedAt);
        }
        if ([] === $items) {
            throw new EmptyOrder();
        }

        $this->id = new Ulid();
        $this->reference = $reference;
        $this->event = $event;
        $this->workspace = $workspace;
        $this->shipping = Money::zero();
        $this->placedAt = $placedAt;
        $this->source = $source;
        $this->lines = new ArrayCollection();
        $this->importedSales = new ArrayCollection();

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
        return new self(self::generateReference($placedAt), $event->workspace(), $event, $placedAt, $items, $discounts, self::MANUAL);
    }

    /**
     * @param list<OrderedItem>     $items
     * @param list<AppliedDiscount> $ruleDiscounts
     */
    public static function imported(Workspace $workspace, string $source, string $externalId, string $reference, ?Event $event, \DateTimeImmutable $placedAt, array $items, Money $charged, Money $shipping, ?PaymentMethod $paymentMethod, array $ruleDiscounts, string $discountLabel): self
    {
        if ($shipping->isNegative()) {
            throw new NegativeShippingCost();
        }

        $order = new self(self::generateReference($placedAt), $workspace, $event, $placedAt, $items, [], $source);
        $order->importedSales->add(new ImportedSale($order, $source, $externalId, $reference, $paymentMethod));
        $order->paymentMethod = $paymentMethod;
        $order->shipping = $shipping;

        $gap = $order->subtotal()->subtract($charged);
        if ($gap->isPositive()) {
            $order->applyDiscounts(self::importDiscounts($gap, $ruleDiscounts, $discountLabel));
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
        return $this->subtotal()->subtract($this->discountTotal())->add($this->shipping);
    }

    public function shipping(): Money
    {
        return $this->shipping;
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

    public function event(): ?Event
    {
        return $this->event;
    }

    public function placedAt(): \DateTimeImmutable
    {
        return $this->placedAt;
    }

    public function isPlacedIn(int $year): bool
    {
        return DateRange::yearOf($this->placedAt) === $year;
    }

    public function source(): string
    {
        return $this->source;
    }

    public function isImported(): bool
    {
        return self::MANUAL !== $this->source;
    }

    /**
     * @return list<ImportedSale>
     */
    public function importedSales(): array
    {
        return array_values($this->importedSales->toArray());
    }

    public function refund(\DateTimeImmutable $refundedAt): void
    {
        if ($this->isRefunded()) {
            throw new OrderAlreadyRefunded($this->reference);
        }

        $this->refundedAt = $refundedAt;
    }

    public function isRefunded(): bool
    {
        return null !== $this->refundedAt;
    }

    public function refundedAt(): ?\DateTimeImmutable
    {
        return $this->refundedAt;
    }

    public function absorb(self $other): void
    {
        if ($other === $this) {
            throw new OrdersNotMergeable('same_order');
        }
        if ($this->isRefunded() || $other->isRefunded()) {
            throw new OrdersNotMergeable('refunded');
        }
        if ($other->event !== $this->event) {
            throw new OrdersNotMergeable('different_event');
        }
        if ($other->source !== $this->source) {
            throw new OrdersNotMergeable('different_source');
        }

        foreach ($other->lines() as $line) {
            $twin = $this->lineTwinOf($line);
            if (null === $twin) {
                $this->lines->add($line->copyInto($this));
                continue;
            }
            $twin->add($line->quantity(), $line->cost());
        }
        foreach ($other->importedSales() as $sale) {
            $sale->joinOrder($this);
            $this->importedSales->add($sale);
        }
        $other->importedSales->clear();

        $this->appliedDiscounts = [...$this->appliedDiscounts, ...$other->appliedDiscounts];
        $this->shipping = $this->shipping->add($other->shipping);
        $this->placedAt = min($this->placedAt, $other->placedAt);
        $this->paymentMethod = PaymentMethod::combined($this->paymentMethod, $other->paymentMethod);
    }

    private function lineTwinOf(OrderLine $line): ?OrderLine
    {
        foreach ($this->lines as $candidate) {
            if ($candidate->sellsSameAs($line) && $candidate->sameUnitAmountsAs($line)) {
                return $candidate;
            }
        }

        return null;
    }

    public function paymentMethod(): ?PaymentMethod
    {
        return $this->paymentMethod;
    }

    /**
     * @return list<OrderLine>
     */
    public function lines(): array
    {
        return array_values($this->lines->toArray());
    }

    /**
     * Sales of a (product, variant) now count for another sellable item, keeping their prices.
     * A line merges into a line already selling that item at the same prices.
     */
    public function moveSales(Ulid $productId, ?string $variant, SellableItem $to): void
    {
        foreach ($this->lines() as $line) {
            if (!($line->productId()?->equals($productId) ?? false) || $line->variant() !== $variant) {
                continue;
            }

            $twin = $this->lineSelling($to, $line);
            if (null === $twin) {
                $line->reassign($to);
                continue;
            }
            $twin->add($line->quantity(), $line->cost());
            $this->lines->removeElement($line);
        }
    }

    private function line(Ulid $lineId): OrderLine
    {
        foreach ($this->lines as $line) {
            if ($line->id()->equals($lineId)) {
                return $line;
            }
        }

        throw new NotFound('order_line', (string) $lineId);
    }

    public function lineToIdentify(Ulid $lineId): OrderLine
    {
        if ($this->isRefunded()) {
            throw new RefundedOrderLocked($this->reference);
        }

        return $this->line($lineId);
    }

    private function lineSelling(SellableItem $item, OrderLine $except): ?OrderLine
    {
        foreach ($this->lines as $line) {
            if ($line !== $except && $line->sells($item) && $line->sameUnitAmountsAs($except)) {
                return $line;
            }
        }

        return null;
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
                $line->add($ordered->quantity, $ordered->cost());

                return;
            }
        }

        $this->lines->add(new OrderLine($this, $ordered->item, $ordered->quantity, $ordered->cost()));
    }

    /**
     * @param list<AppliedDiscount> $discounts
     */
    private function applyDiscounts(array $discounts): void
    {
        $total = Money::sum(array_map(static fn (AppliedDiscount $discount): Money => $discount->amount, $discounts));
        if ($total->greaterThan($this->subtotal())) {
            throw new DiscountExceedsSubtotal();
        }

        $this->appliedDiscounts = array_map(static fn (AppliedDiscount $discount): array => $discount->toArray(), $discounts);
    }

    /**
     * @param list<AppliedDiscount> $ruleDiscounts
     *
     * @return list<AppliedDiscount>
     */
    private static function importDiscounts(Money $gap, array $ruleDiscounts, string $label): array
    {
        $ruleSaving = Money::sum(array_map(static fn (AppliedDiscount $discount): Money => $discount->amount, $ruleDiscounts));
        $rounding = abs($gap->subtract($ruleSaving)->amount());

        return [] !== $ruleDiscounts && $rounding <= self::IMPORT_ROUNDING_TOLERANCE_CENTS
            ? $ruleDiscounts
            : [new AppliedDiscount($label, $gap)];
    }

    private static function generateReference(\DateTimeImmutable $placedAt): string
    {
        return \sprintf('CMD-%s-%s', $placedAt->format('Ymd'), substr((string) new Ulid(), -6));
    }

    public function workspace(): Workspace
    {
        return $this->workspace;
    }
}
