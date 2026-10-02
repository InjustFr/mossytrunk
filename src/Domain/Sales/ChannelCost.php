<?php

declare(strict_types=1);

namespace App\Domain\Sales;

use App\Domain\Sales\Exception\EmptyCostLabel;
use App\Domain\Shared\Exception\NegativeAmount;
use App\Domain\Shared\Money;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

#[ORM\Entity]
#[ORM\Table(name: 'sales_channel_cost')]
class ChannelCost
{
    #[ORM\Id]
    #[ORM\Column(type: UlidType::NAME, unique: true)]
    private Ulid $id;

    #[ORM\ManyToOne(targetEntity: SalesChannel::class, inversedBy: 'costs')]
    #[ORM\JoinColumn(name: 'channel_id', nullable: false, onDelete: 'CASCADE')]
    private SalesChannel $channel;

    #[ORM\Column(length: 100)]
    private string $label;

    #[ORM\Column(length: 16, enumType: ChannelCostKind::class)]
    private ChannelCostKind $kind;

    #[ORM\Column]
    private int $amount;

    /**
     * @internal built by SalesChannel
     */
    public function __construct(SalesChannel $channel, string $label, ChannelCostKind $kind, int $amount)
    {
        $this->id = new Ulid();
        $this->channel = $channel;
        $this->revise($label, $kind, $amount);
    }

    /**
     * @internal revised through SalesChannel
     */
    public function revise(string $label, ChannelCostKind $kind, int $amount): void
    {
        $label = trim($label);
        if ('' === $label) {
            throw new EmptyCostLabel();
        }
        if ($amount < 0) {
            throw new NegativeAmount('channel_cost');
        }

        $this->label = $label;
        $this->kind = $kind;
        $this->amount = $amount;
    }

    public function on(Money $orderTotal): OrderCharge
    {
        return new OrderCharge($this->label, match ($this->kind) {
            ChannelCostKind::Fixed => Money::cents($this->amount),
            ChannelCostKind::Percent => $orderTotal->percentage($this->amount),
        });
    }

    public function id(): Ulid
    {
        return $this->id;
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
