<?php

declare(strict_types=1);

namespace App\Domain\Shared;

use App\Domain\Shared\Exception\DateRangeEndsBeforeStart;
use Doctrine\ORM\Mapping as ORM;

/**
 * Inclusive range of whole calendar days in the business time zone (Europe/Paris).
 * A moment is covered when its local date falls between start and end dates.
 */
#[ORM\Embeddable]
final readonly class DateRange
{
    public const string TIMEZONE = 'Europe/Paris';

    private function __construct(
        #[ORM\Column(type: 'date_immutable')]
        private \DateTimeImmutable $start,
        #[ORM\Column(type: 'date_immutable')]
        private \DateTimeImmutable $end,
    ) {
    }

    public static function fromDates(\DateTimeImmutable $start, \DateTimeImmutable $end): self
    {
        $start = self::toDay($start);
        $end = self::toDay($end);

        if ($end < $start) {
            throw new DateRangeEndsBeforeStart();
        }

        return new self($start, $end);
    }

    public static function year(int $year): self
    {
        $timezone = new \DateTimeZone(self::TIMEZONE);

        return new self(new \DateTimeImmutable(\sprintf('%04d-01-01', $year), $timezone), new \DateTimeImmutable(\sprintf('%04d-12-31', $year), $timezone));
    }

    public function start(): \DateTimeImmutable
    {
        return $this->start;
    }

    public function end(): \DateTimeImmutable
    {
        return $this->end;
    }

    public function days(): int
    {
        return $this->start->diff($this->end)->days + 1;
    }

    public static function yearOf(\DateTimeImmutable $moment): int
    {
        return (int) self::toDay($moment)->format('Y');
    }

    public function covers(\DateTimeImmutable $moment): bool
    {
        $day = self::toDay($moment)->format('Y-m-d');

        return $day >= $this->start->format('Y-m-d') && $day <= $this->end->format('Y-m-d');
    }

    /**
     * True when the whole range is after the moment's local day.
     */
    public function isAfter(\DateTimeImmutable $moment): bool
    {
        return $this->start->format('Y-m-d') > self::toDay($moment)->format('Y-m-d');
    }

    /**
     * True when the whole range is before the moment's local day.
     */
    public function isBefore(\DateTimeImmutable $moment): bool
    {
        return $this->end->format('Y-m-d') < self::toDay($moment)->format('Y-m-d');
    }

    public function overlaps(self $other): bool
    {
        return $this->start->format('Y-m-d') <= $other->end->format('Y-m-d')
            && $other->start->format('Y-m-d') <= $this->end->format('Y-m-d');
    }

    /**
     * Midnight of the moment's local date in the business time zone.
     */
    private static function toDay(\DateTimeImmutable $moment): \DateTimeImmutable
    {
        $local = $moment->setTimezone(new \DateTimeZone(self::TIMEZONE));

        return new \DateTimeImmutable($local->format('Y-m-d'), new \DateTimeZone(self::TIMEZONE));
    }
}
