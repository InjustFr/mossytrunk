<?php

declare(strict_types=1);

namespace App\Domain\Order;

use App\Domain\Identity\Workspace;
use App\Domain\Shared\Money;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

#[ORM\Entity]
#[ORM\Table(name: 'order_imported_sale')]
#[ORM\UniqueConstraint(name: 'imported_sale_workspace_source_external_id', columns: ['workspace_id', 'source', 'external_id'])]
class ImportedSale
{
    #[ORM\Id]
    #[ORM\Column(type: UlidType::NAME, unique: true)]
    private Ulid $id;

    #[ORM\ManyToOne(targetEntity: Order::class, inversedBy: 'importedSales')]
    #[ORM\JoinColumn(name: 'order_id', nullable: false, onDelete: 'CASCADE')]
    private Order $order;

    #[ORM\ManyToOne(targetEntity: Workspace::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Workspace $workspace;

    #[ORM\Column(length: 32)]
    private string $source;

    #[ORM\Column(length: 64)]
    private string $externalId;

    #[ORM\Column(length: 64)]
    private string $reference;

    #[ORM\Column(length: 16, nullable: true, enumType: PaymentMethod::class)]
    private ?PaymentMethod $paymentMethod;

    #[ORM\Column(nullable: true)]
    private ?int $fee = null;

    public function __construct(Order $order, string $source, string $externalId, string $reference, ?PaymentMethod $paymentMethod)
    {
        $this->id = new Ulid();
        $this->order = $order;
        $this->workspace = $order->workspace();
        $this->source = $source;
        $this->externalId = $externalId;
        $this->reference = $reference;
        $this->paymentMethod = $paymentMethod;
    }

    public function joinOrder(Order $order): void
    {
        $this->order = $order;
    }

    public function settleFee(Money $fee): void
    {
        $this->fee = $fee->amount();
    }

    public function isFrom(string $source, string $externalId): bool
    {
        return $this->source === $source && $this->externalId === $externalId;
    }

    public function source(): string
    {
        return $this->source;
    }

    public function externalId(): string
    {
        return $this->externalId;
    }

    public function reference(): string
    {
        return $this->reference;
    }

    public function paymentMethod(): ?PaymentMethod
    {
        return $this->paymentMethod;
    }

    public function fee(): ?Money
    {
        return null === $this->fee ? null : Money::cents($this->fee);
    }
}
