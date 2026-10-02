<?php

declare(strict_types=1);

namespace App\Domain\Integration;

use App\Domain\Order\PaymentMethod;
use App\Domain\Sales\ChannelCostKind;
use App\Domain\Sales\Exception\EmptyCostLabel;
use App\Domain\Sales\OrderCharge;
use App\Domain\Shared\Exception\NegativeAmount;
use App\Domain\Shared\Money;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

#[ORM\Entity]
#[ORM\Table(name: 'service_payment_fee')]
class PaymentFee
{
    #[ORM\Id]
    #[ORM\Column(type: UlidType::NAME, unique: true)]
    private Ulid $id;

    #[ORM\ManyToOne(targetEntity: ServiceConnection::class, inversedBy: 'fees')]
    #[ORM\JoinColumn(name: 'connection_id', nullable: false, onDelete: 'CASCADE')]
    private ServiceConnection $connection;

    #[ORM\Column(length: 16, enumType: PaymentMethod::class)]
    private PaymentMethod $paymentMethod;

    #[ORM\Column(length: 100)]
    private string $label;

    #[ORM\Column(length: 16, enumType: ChannelCostKind::class)]
    private ChannelCostKind $kind;

    #[ORM\Column]
    private int $amount;

    /**
     * @internal built by ServiceConnection
     */
    public function __construct(ServiceConnection $connection, PaymentMethod $paymentMethod, string $label, ChannelCostKind $kind, int $amount)
    {
        $this->id = new Ulid();
        $this->connection = $connection;
        $this->revise($paymentMethod, $label, $kind, $amount);
    }

    /**
     * @internal revised through ServiceConnection
     */
    public function revise(PaymentMethod $paymentMethod, string $label, ChannelCostKind $kind, int $amount): void
    {
        $label = trim($label);
        if ('' === $label) {
            throw new EmptyCostLabel();
        }
        if ($amount < 0) {
            throw new NegativeAmount('channel_cost');
        }

        $this->paymentMethod = $paymentMethod;
        $this->label = $label;
        $this->kind = $kind;
        $this->amount = $amount;
    }

    public function appliesTo(?PaymentMethod $paymentMethod): bool
    {
        return $this->paymentMethod === $paymentMethod;
    }

    public function on(Money $orderTotal): OrderCharge
    {
        return new OrderCharge($this->label, $this->kind->on($this->amount, $orderTotal));
    }

    public function id(): Ulid
    {
        return $this->id;
    }

    public function paymentMethod(): PaymentMethod
    {
        return $this->paymentMethod;
    }

    public function label(): string
    {
        return $this->label;
    }

    public function kind(): ChannelCostKind
    {
        return $this->kind;
    }

    public function amount(): int
    {
        return $this->amount;
    }
}
