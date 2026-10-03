<?php

declare(strict_types=1);

namespace App\Domain\Order;

use App\Domain\Discount\AppliedDiscount;
use App\Domain\Event\Event;
use App\Domain\Identity\Workspace;
use App\Domain\Order\Exception\DiscountExceedsSubtotal;
use App\Domain\Order\Exception\EmptyOrder;
use App\Domain\Order\Exception\NegativePostage;
use App\Domain\Order\Exception\NegativeShippingCost;
use App\Domain\Order\Exception\OrderAlreadyRefunded;
use App\Domain\Order\Exception\OrderOutsideEvent;
use App\Domain\Order\Exception\OrdersNotMergeable;
use App\Domain\Order\Exception\RefundedOrderLocked;
use App\Domain\Order\Exception\SupplyNotOnChannel;
use App\Domain\Product\SellableItem;
use App\Domain\Reference\Referenced;
use App\Domain\Reference\ReferenceSubject;
use App\Domain\Sales\Exception\MarketOrderWithoutEvent;
use App\Domain\Sales\OrderCharge;
use App\Domain\Sales\SalesChannel;
use App\Domain\Shared\CostAllocation;
use App\Domain\Shared\DateRange;
use App\Domain\Shared\Exception\NegativeAmount;
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
class Order implements Referenced
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

    #[ORM\ManyToOne(targetEntity: SalesChannel::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?SalesChannel $channel = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    private \DateTimeImmutable $placedAt;

    /** @var Collection<int, OrderLine> */
    #[ORM\OneToMany(targetEntity: OrderLine::class, mappedBy: 'order', cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['id' => 'ASC'])]
    private Collection $lines;

    /** @var Collection<int, OrderSupply> */
    #[ORM\OneToMany(targetEntity: OrderSupply::class, mappedBy: 'order', cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['id' => 'ASC'])]
    private Collection $supplies;

    /** @var list<array{label: string, amount: int}> */
    #[ORM\Column(type: Types::JSON)]
    private array $appliedDiscounts = [];

    #[ORM\Column(length: 32)]
    private string $source;

    /** @var Collection<int, ImportedSale> */
    #[ORM\OneToMany(targetEntity: ImportedSale::class, mappedBy: 'order', cascade: ['persist'])]
    #[ORM\OrderBy(['id' => 'ASC'])]
    private Collection $importedSales;

    #[ORM\Column(length: 16, nullable: true, enumType: PaymentMethod::class)]
    private ?PaymentMethod $paymentMethod = null;

    #[ORM\Embedded(class: Money::class, columnPrefix: 'shipping_')]
    private Money $shipping;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $refundedAt = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $checkedAt = null;

    /** @var list<array{label: string, amount: int}> */
    #[ORM\Column(type: Types::JSON, options: ['default' => '[]'])]
    private array $channelCharges = [];

    #[ORM\Embedded(class: Money::class, columnPrefix: 'postage_')]
    private Money $postage;

    #[ORM\Embedded(class: Money::class, columnPrefix: 'subtotal_')]
    private Money $subtotal;

    #[ORM\Embedded(class: Money::class, columnPrefix: 'discount_total_')]
    private Money $discountTotal;

    #[ORM\Embedded(class: Money::class, columnPrefix: 'total_')]
    private Money $total;

    #[ORM\Embedded(class: Money::class, columnPrefix: 'cost_of_goods_')]
    private Money $costOfGoods;

    #[ORM\Embedded(class: Money::class, columnPrefix: 'supplies_cost_')]
    private Money $suppliesCost;

    #[ORM\Embedded(class: Money::class, columnPrefix: 'channel_costs_')]
    private Money $channelCosts;

    /**
     * @param list<OrderedItem>     $items
     * @param list<AppliedDiscount> $discounts
     */
    private function __construct(string $reference, Workspace $workspace, ?Event $event, \DateTimeImmutable $placedAt, array $items, array $discounts, string $source, ?SalesChannel $channel)
    {
        if (null !== $event && !$event->covers($placedAt)) {
            throw new OrderOutsideEvent($event->name(), $placedAt);
        }
        if (null === $event && null !== $channel && !$channel->acceptsOrderWithoutEvent()) {
            throw new MarketOrderWithoutEvent($channel->name());
        }
        if ([] === $items) {
            throw new EmptyOrder();
        }

        $this->id = new Ulid();
        $this->reference = $reference;
        $this->event = $event;
        $this->channel = $channel;
        $this->workspace = $workspace;
        $this->shipping = Money::zero();
        $this->postage = Money::zero();
        $this->channelCosts = Money::zero();
        $this->placedAt = $placedAt;
        $this->source = $source;
        $this->lines = new ArrayCollection();
        $this->supplies = new ArrayCollection();
        $this->importedSales = new ArrayCollection();

        foreach ($items as $item) {
            $this->addItem($item);
        }

        $this->applyDiscounts($discounts);
        $this->chargeChannelCosts();
    }

    /**
     * @param list<OrderedItem>     $items
     * @param list<AppliedDiscount> $discounts computed by the DiscountCalculator
     */
    public static function place(string $reference, Event $event, \DateTimeImmutable $placedAt, array $items, array $discounts, ?SalesChannel $channel = null): self
    {
        return new self($reference, $event->workspace(), $event, $placedAt, $items, $discounts, self::MANUAL, $channel);
    }

    /**
     * @param list<OrderedItem>     $items
     * @param list<AppliedDiscount> $ruleDiscounts
     */
    public static function imported(string $reference, Workspace $workspace, string $source, string $externalId, string $saleReference, ?Event $event, \DateTimeImmutable $placedAt, array $items, Money $charged, Money $shipping, ?PaymentMethod $paymentMethod, array $ruleDiscounts, string $discountLabel, ?SalesChannel $channel = null): self
    {
        if ($shipping->isNegative()) {
            throw new NegativeShippingCost();
        }

        $order = new self($reference, $workspace, $event, $placedAt, $items, [], $source, $channel);
        $order->importedSales->add(new ImportedSale($order, $source, $externalId, $saleReference, $paymentMethod));
        $order->paymentMethod = $paymentMethod;
        $order->shipping = $shipping;

        $gap = $order->linesTotal()->subtract($charged);
        if ($gap->isPositive()) {
            $order->applyDiscounts(self::importDiscounts($gap, $ruleDiscounts, $discountLabel));
        }
        $order->chargeChannelCosts();

        return $order;
    }

    public function subtotal(): Money
    {
        return $this->subtotal;
    }

    public function discountTotal(): Money
    {
        return $this->discountTotal;
    }

    public function total(): Money
    {
        return $this->total;
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
        return $this->costOfGoods;
    }

    public function id(): Ulid
    {
        return $this->id;
    }

    public function reference(): string
    {
        return $this->reference;
    }

    public function referenceSubject(): ReferenceSubject
    {
        return ReferenceSubject::at($this->placedAt);
    }

    public function changeReference(string $reference): void
    {
        $this->reference = $reference;
    }

    public function channel(): ?SalesChannel
    {
        return $this->channel;
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

    public function settleSaleFee(string $source, string $externalId, Money $fee): void
    {
        if ($fee->isNegative()) {
            throw new NegativeAmount('payment_fee');
        }

        foreach ($this->importedSales as $sale) {
            if ($sale->isFrom($source, $externalId)) {
                $sale->settleFee($fee);
            }
        }
    }

    public function saleFees(): ?Money
    {
        if ($this->importedSales->isEmpty()) {
            return null;
        }

        $total = Money::zero();
        foreach ($this->importedSales as $sale) {
            $fee = $sale->fee();
            if (null === $fee) {
                return null;
            }
            $total = $total->add($fee);
        }

        return $total;
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

    public function check(\DateTimeImmutable $checkedAt): void
    {
        $this->checkedAt ??= $checkedAt;
    }

    public function uncheck(): void
    {
        $this->checkedAt = null;
    }

    public function isChecked(): bool
    {
        return null !== $this->checkedAt;
    }

    public function checkedAt(): ?\DateTimeImmutable
    {
        return $this->checkedAt;
    }

    public function canAbsorb(self $other): bool
    {
        return null === $this->mergeObstacleWith($other);
    }

    public function absorb(self $other): void
    {
        $obstacle = $this->mergeObstacleWith($other);
        if (null !== $obstacle) {
            throw new OrdersNotMergeable($obstacle);
        }

        foreach ($other->lines() as $line) {
            $twin = $this->lineTwinOf($line);
            if (null === $twin) {
                $this->lines->add($line->copyInto($this));
                continue;
            }
            $twin->add($line->quantity(), $line->cost());
        }
        foreach ($other->supplies() as $supply) {
            $twin = $this->supplyUsing($supply->productId(), $supply->variant());
            if (null === $twin) {
                $this->supplies->add($supply->copyInto($this));
                continue;
            }
            $twin->add($supply->quantity(), $supply->cost());
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
        $this->postage = $this->postage->add($other->postage);
        $this->chargeChannelCosts();
    }

    /**
     * @param list<OrderCharge> $paymentFees
     */
    public function chargeChannelCosts(array $paymentFees = []): void
    {
        $this->recordSales();
        $charges = [...(null === $this->channel ? [] : $this->channel->chargesOn($this->total)), ...$paymentFees];
        $this->channelCharges = array_map(static fn (OrderCharge $charge): array => $charge->toArray(), $charges);
        $this->recordChannelCosts();
    }

    /**
     * @return list<OrderCharge>
     */
    public function channelCharges(): array
    {
        return array_map(OrderCharge::fromArray(...), $this->channelCharges);
    }

    public function stamp(Money $postage): void
    {
        if ($postage->isNegative()) {
            throw new NegativePostage();
        }

        $this->postage = $postage;
        $this->recordChannelCosts();
    }

    public function postage(): Money
    {
        return $this->postage;
    }

    public function channelCosts(): Money
    {
        return $this->channelCosts;
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

    public function fillMissingCosts(Ulid $productId, ?string $variant, Money $unitCost): int
    {
        if ($unitCost->isZero()) {
            return 0;
        }
        $filled = 0;
        foreach ($this->lines as $line) {
            if ($line->hasUnknownCost() && ($line->productId()?->equals($productId) ?? false) && $line->variant() === $variant) {
                $line->costAt($unitCost);
                ++$filled;
            }
        }
        $this->recordSales();

        return $filled;
    }

    public function unknownCostLines(): int
    {
        return \count(array_filter($this->lines(), static fn (OrderLine $line): bool => $line->hasUnknownCost()));
    }

    public function useSupply(SellableItem $supply, int $quantity, Money $cost): void
    {
        if ($this->isRefunded()) {
            throw new RefundedOrderLocked($this->reference);
        }
        $productId = $supply->productId;
        if (null === $productId || null === $this->channel || !$this->channel->offers($productId)) {
            throw new SupplyNotOnChannel($supply->label(), $this->reference);
        }

        $twin = $this->supplyUsing($productId, $supply->variant);
        if (null === $twin) {
            $this->supplies->add(new OrderSupply($this, $productId, $supply, $quantity, $cost));
        } else {
            $twin->add($quantity, $cost);
        }
        $this->recordSales();
    }

    public function returnSupply(Ulid $supplyLineId): OrderSupply
    {
        if ($this->isRefunded()) {
            throw new RefundedOrderLocked($this->reference);
        }
        foreach ($this->supplies as $supply) {
            if ($supply->id()->equals($supplyLineId)) {
                $this->supplies->removeElement($supply);
                $this->recordSales();

                return $supply;
            }
        }

        throw new NotFound('order_supply', (string) $supplyLineId);
    }

    /**
     * @return list<OrderSupply>
     */
    public function supplies(): array
    {
        return array_values($this->supplies->toArray());
    }

    public function suppliesCost(): Money
    {
        return $this->suppliesCost;
    }

    private function supplyUsing(Ulid $productId, ?string $variant): ?OrderSupply
    {
        foreach ($this->supplies as $supply) {
            if ($supply->uses($productId, $variant)) {
                return $supply;
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
        $this->recordSales();
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

    public function identifyLine(Ulid $lineId, SellableItem $item, Money $cost): void
    {
        $this->lineToIdentify($lineId)->identify($item, $cost);
        $this->recordSales();
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
        if ($total->greaterThan($this->linesTotal())) {
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

    public function workspace(): Workspace
    {
        return $this->workspace;
    }

    private function linesTotal(): Money
    {
        return Money::sum($this->lines->map(static fn (OrderLine $line): Money => $line->total()));
    }

    private function recordSales(): void
    {
        $lines = $this->lines();
        $this->subtotal = $this->linesTotal();
        $this->discountTotal = Money::sum(array_map(static fn (AppliedDiscount $discount): Money => $discount->amount, $this->appliedDiscounts()));
        $this->total = $this->subtotal->subtract($this->discountTotal)->add($this->shipping);
        $shares = CostAllocation::proportionally($this->discountTotal, array_map(static fn (OrderLine $line): Money => $line->total(), $lines));
        foreach ($lines as $index => $line) {
            $line->shareDiscount($shares[$index]);
        }
        $this->costOfGoods = Money::sum(array_map(static fn (OrderLine $line): Money => $line->cost(), $lines));
        $this->suppliesCost = Money::sum(array_map(static fn (OrderSupply $supply): Money => $supply->cost(), $this->supplies()));
    }

    private function recordChannelCosts(): void
    {
        $this->channelCosts = Money::sum(array_map(static fn (OrderCharge $charge): Money => $charge->amount, $this->channelCharges()))->add($this->postage);
    }

    private function mergeObstacleWith(self $other): ?string
    {
        return match (true) {
            $other === $this => 'same_order',
            $this->isRefunded() || $other->isRefunded() => 'refunded',
            $other->event !== $this->event => 'different_event',
            $other->source !== $this->source => 'different_source',
            default => null,
        };
    }
}
