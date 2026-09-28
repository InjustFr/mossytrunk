<?php

declare(strict_types=1);

namespace App\Domain\Event;

use App\Domain\Shared\InvalidMoney;
use App\Domain\Shared\Money;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

/**
 * Money spent for an event (stand fee, travel, lodging…). Created only through {@see Event::addExpense()}.
 */
#[ORM\Entity]
#[ORM\Table(name: 'event_expense')]
class Expense
{
    #[ORM\Id]
    #[ORM\Column(type: UlidType::NAME, unique: true)]
    private Ulid $id;

    #[ORM\ManyToOne(targetEntity: Event::class, inversedBy: 'expenses')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Event $event;

    #[ORM\Column(length: 255)]
    private string $label;

    #[ORM\Embedded(class: Money::class, columnPrefix: 'amount_')]
    private Money $amount;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    /**
     * @internal use Event::addExpense()
     */
    public function __construct(Event $event, string $label, Money $amount)
    {
        $label = trim($label);
        if ('' === $label) {
            throw InvalidEvent::emptyExpenseLabel();
        }
        if (!$amount->isPositive()) {
            throw InvalidMoney::mustBePositive('Le montant de la dépense');
        }

        $this->id = new Ulid();
        $this->event = $event;
        $this->label = $label;
        $this->amount = $amount;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function id(): Ulid
    {
        return $this->id;
    }

    public function label(): string
    {
        return $this->label;
    }

    public function amount(): Money
    {
        return $this->amount;
    }
}
