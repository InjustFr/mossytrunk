<?php

declare(strict_types=1);

namespace App\Domain\Accounting;

use App\Domain\Shared\DateRange;

final readonly class DeclarationPeriod
{
    private function __construct(
        public DeclarationPeriodicity $periodicity,
        public int $year,
        public int $index,
    ) {
    }

    public static function containing(\DateTimeImmutable $moment, DeclarationPeriodicity $periodicity): self
    {
        $local = $moment->setTimezone(new \DateTimeZone(DateRange::TIMEZONE));

        return new self($periodicity, (int) $local->format('Y'), intdiv((int) $local->format('n') - 1, $periodicity->months()) + 1);
    }

    /**
     * @return list<self>
     */
    public static function ofYear(int $year, DeclarationPeriodicity $periodicity): array
    {
        return array_map(static fn (int $index): self => new self($periodicity, $year, $index), range(1, intdiv(12, $periodicity->months())));
    }

    public static function fromKey(string $key): self
    {
        if (1 === preg_match('/^(\d{4})-(\d{2})$/', $key, $month)) {
            $index = (int) $month[2];
            if ($index >= 1 && $index <= 12) {
                return new self(DeclarationPeriodicity::Monthly, (int) $month[1], $index);
            }
        }
        if (1 === preg_match('/^(\d{4})-T([1-4])$/', $key, $quarter)) {
            return new self(DeclarationPeriodicity::Quarterly, (int) $quarter[1], (int) $quarter[2]);
        }

        throw InvalidDeclaration::unknownPeriod($key);
    }

    public function key(): string
    {
        return DeclarationPeriodicity::Monthly === $this->periodicity
            ? \sprintf('%d-%02d', $this->year, $this->index)
            : \sprintf('%d-T%d', $this->year, $this->index);
    }

    public function start(): \DateTimeImmutable
    {
        $month = ($this->index - 1) * $this->periodicity->months() + 1;

        return new \DateTimeImmutable(\sprintf('%d-%02d-01', $this->year, $month), new \DateTimeZone(DateRange::TIMEZONE));
    }

    public function end(): \DateTimeImmutable
    {
        return $this->start()->modify(\sprintf('+%d months -1 day', $this->periodicity->months()));
    }

    public function deadline(): \DateTimeImmutable
    {
        return $this->end()->modify('last day of next month');
    }

    public function range(): DateRange
    {
        return DateRange::fromDates($this->start(), $this->end());
    }

    public function covers(\DateTimeImmutable $moment): bool
    {
        return $this->range()->covers($moment);
    }

    public function isOverOn(\DateTimeImmutable $moment): bool
    {
        return $moment->setTimezone(new \DateTimeZone(DateRange::TIMEZONE))->format('Y-m-d') > $this->end()->format('Y-m-d');
    }

    public function isLateOn(\DateTimeImmutable $moment): bool
    {
        return $moment->setTimezone(new \DateTimeZone(DateRange::TIMEZONE))->format('Y-m-d') > $this->deadline()->format('Y-m-d');
    }
}
