<?php

declare(strict_types=1);

namespace App\Domain\Purchasing;

use App\Domain\Identity\Workspace;
use App\Domain\Shared\Money;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

#[ORM\Entity]
#[ORM\Table(name: 'supplier_order')]
#[ORM\UniqueConstraint(name: 'supplier_order_workspace_reference', columns: ['workspace_id', 'reference'])]
class SupplierOrder
{
    #[ORM\Id]
    #[ORM\Column(type: UlidType::NAME, unique: true)]
    private Ulid $id;

    #[ORM\ManyToOne(targetEntity: Workspace::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Workspace $workspace;

    #[ORM\Column(length: 64)]
    private string $reference;

    #[ORM\ManyToOne(targetEntity: Supplier::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Supplier $supplier;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private \DateTimeImmutable $orderedOn;

    #[ORM\Column(length: 16, enumType: SupplierOrderStatus::class)]
    private SupplierOrderStatus $status = SupplierOrderStatus::Ordered;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $receivedAt = null;

    /** @var Collection<int, SupplierOrderLine> */
    #[ORM\OneToMany(targetEntity: SupplierOrderLine::class, mappedBy: 'order', cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $lines;

    /**
     * @param list<PurchasedItem> $items
     */
    private function __construct(Supplier $supplier, \DateTimeImmutable $orderedOn, array $items)
    {
        $this->id = new Ulid();
        $this->workspace = $supplier->workspace();
        $this->reference = \sprintf('CMF-%s-%s', $orderedOn->format('Ymd'), substr((string) new Ulid(), -6));
        $this->lines = new ArrayCollection();
        $this->revise($supplier, $orderedOn, $items);
    }

    /**
     * @param list<PurchasedItem> $items
     */
    public static function place(Supplier $supplier, \DateTimeImmutable $orderedOn, array $items): self
    {
        return new self($supplier, $orderedOn, $items);
    }

    /**
     * @param list<PurchasedItem> $items
     */
    public function revise(Supplier $supplier, \DateTimeImmutable $orderedOn, array $items): void
    {
        $this->assertStillOrdered();
        if ([] === $items) {
            throw InvalidPurchase::emptyOrder();
        }

        $this->supplier = $supplier;
        $this->orderedOn = $orderedOn;
        $this->lines->clear();
        foreach ($items as $position => $purchased) {
            foreach ($this->lines as $line) {
                if ($line->isFor($purchased->item->productId, $purchased->item->variant)) {
                    throw InvalidPurchase::orderedTwice($purchased->item->label());
                }
            }
            $this->lines->add(new SupplierOrderLine($this, $purchased, $position));
        }
    }

    /**
     * @param array<string, int> $receivedQuantities
     *
     * @return list<SupplierOrderLine>
     */
    public function receive(array $receivedQuantities, \DateTimeImmutable $receivedAt): array
    {
        $this->assertStillOrdered();

        foreach ($this->lines() as $line) {
            $quantity = $receivedQuantities[(string) $line->id()] ?? throw InvalidPurchase::receivedQuantityMissing($line->label());
            $line->receive($quantity);
        }
        $this->status = SupplierOrderStatus::Received;
        $this->receivedAt = $receivedAt;

        return array_values(array_filter($this->lines(), static fn (SupplierOrderLine $line): bool => $line->receivedQuantity() > 0));
    }

    public function assertStillOrdered(): void
    {
        if (SupplierOrderStatus::Received === $this->status) {
            throw InvalidPurchase::alreadyReceived($this->reference);
        }
    }

    public function total(): Money
    {
        return Money::sum(array_map(static fn (SupplierOrderLine $line): Money => $line->totalPrice(), $this->lines()));
    }

    public function orderedUnits(): int
    {
        return array_sum(array_map(static fn (SupplierOrderLine $line): int => $line->orderedQuantity(), $this->lines()));
    }

    public function receivedUnits(): ?int
    {
        return SupplierOrderStatus::Received === $this->status
            ? array_sum(array_map(static fn (SupplierOrderLine $line): int => (int) $line->receivedQuantity(), $this->lines()))
            : null;
    }

    public function id(): Ulid
    {
        return $this->id;
    }

    public function reference(): string
    {
        return $this->reference;
    }

    public function supplier(): Supplier
    {
        return $this->supplier;
    }

    public function orderedOn(): \DateTimeImmutable
    {
        return $this->orderedOn;
    }

    public function status(): SupplierOrderStatus
    {
        return $this->status;
    }

    public function receivedAt(): ?\DateTimeImmutable
    {
        return $this->receivedAt;
    }

    /**
     * @return list<SupplierOrderLine>
     */
    public function lines(): array
    {
        $lines = array_values($this->lines->toArray());
        usort($lines, static fn (SupplierOrderLine $a, SupplierOrderLine $b): int => $a->position() <=> $b->position());

        return $lines;
    }

    public function workspace(): Workspace
    {
        return $this->workspace;
    }
}
