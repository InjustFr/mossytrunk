<?php

declare(strict_types=1);

namespace App\Domain\Discount;

use App\Domain\Shared\DateRange;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
final readonly class ValidityPeriod
{
    private function __construct(
        #[ORM\Column(type: 'date_immutable', nullable: true)]
        private ?\DateTimeImmutable $start,
        #[ORM\Column(type: 'date_immutable', nullable: true)]
        private ?\DateTimeImmutable $end,
    ) {
    }

    public static function between(?\DateTimeImmutable $start, ?\DateTimeImmutable $end): self
    {
        $start = null === $start ? null : self::day($start);
        $end = null === $end ? null : self::day($end);

        if (null !== $start && null !== $end && $end < $start) {
            throw InvalidDiscountRule::endsBeforeStart();
        }

        return new self($start, $end);
    }

    public static function always(): self
    {
        return new self(null, null);
    }

    public function covers(\DateTimeImmutable $moment): bool
    {
        $day = self::day($moment->setTimezone(new \DateTimeZone(DateRange::TIMEZONE)))->format('Y-m-d');

        return (null === $this->start || $day >= $this->start->format('Y-m-d'))
            && (null === $this->end || $day <= $this->end->format('Y-m-d'));
    }

    public function statusOn(\DateTimeImmutable $moment): DiscountStatus
    {
        $day = self::day($moment->setTimezone(new \DateTimeZone(DateRange::TIMEZONE)))->format('Y-m-d');

        return match (true) {
            null !== $this->end && $day > $this->end->format('Y-m-d') => DiscountStatus::Expired,
            null !== $this->start && $day < $this->start->format('Y-m-d') => DiscountStatus::Upcoming,
            default => DiscountStatus::Running,
        };
    }

    public function startingOn(\DateTimeImmutable $moment): self
    {
        return self::between($moment->setTimezone(new \DateTimeZone(DateRange::TIMEZONE)), $this->end);
    }

    public function endingBefore(\DateTimeImmutable $moment): self
    {
        $yesterday = self::day($moment->setTimezone(new \DateTimeZone(DateRange::TIMEZONE)))->modify('-1 day');
        if (null !== $this->start && $this->start > $yesterday) {
            throw InvalidDiscountRule::startedToday();
        }

        return self::between($this->start, $yesterday);
    }

    public function start(): ?\DateTimeImmutable
    {
        return $this->start;
    }

    public function end(): ?\DateTimeImmutable
    {
        return $this->end;
    }

    private static function day(\DateTimeImmutable $date): \DateTimeImmutable
    {
        return new \DateTimeImmutable($date->format('Y-m-d'), new \DateTimeZone(DateRange::TIMEZONE));
    }
}
