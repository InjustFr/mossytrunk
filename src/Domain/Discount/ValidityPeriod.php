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
