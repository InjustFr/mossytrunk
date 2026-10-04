<?php

declare(strict_types=1);

namespace App\Domain\Event;

use App\Domain\Event\Exception\EmptyExpenseLabel;
use App\Domain\Shared\Exception\NonPositiveAmount;
use App\Domain\Shared\Money;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

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

    #[ORM\Column(nullable: true)]
    private ?int $sharedOverEvents = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $sharedUntil = null;

    public function __construct(Event $event, string $label, Money $amount, ExpenseSpread $spread)
    {
        $this->id = new Ulid();
        $this->event = $event;
        $this->createdAt = new \DateTimeImmutable();
        $this->revise($label, $amount, $spread);
    }

    public function revise(string $label, Money $amount, ExpenseSpread $spread): void
    {
        $label = trim($label);
        if ('' === $label) {
            throw new EmptyExpenseLabel();
        }
        if (!$amount->isPositive()) {
            throw new NonPositiveAmount('expense_amount');
        }

        $this->label = $label;
        $this->amount = $amount;
        $this->sharedOverEvents = $spread->events;
        $this->sharedUntil = $spread->until;
    }

    public function spread(): ExpenseSpread
    {
        return ExpenseSpread::of($this->sharedOverEvents, $this->sharedUntil);
    }

    public function event(): Event
    {
        return $this->event;
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
