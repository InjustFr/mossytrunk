<?php

declare(strict_types=1);

namespace App\Application\Accounting\GetUrssafOverview;

use App\Application\Accounting\PeriodTurnover;
use App\Domain\Accounting\UrssafDeclaration;
use App\Domain\Reporting\UrssafContribution;

final readonly class DeclarationPeriodView
{
    public function __construct(
        public string $key,
        public string $periodicity,
        public int $year,
        public int $index,
        public string $start,
        public string $end,
        public string $deadline,
        public int $turnover,
        public int $orderCount,
        public int $contribution,
        public string $status,
        public ?int $declaredTurnover,
        public ?string $declaredAt,
    ) {
    }

    public static function of(PeriodTurnover $figures, ?UrssafDeclaration $declaration, \DateTimeImmutable $today, \DateTimeImmutable $activityStart): self
    {
        $period = $figures->period;

        return new self(
            $period->key(),
            $period->periodicity->value,
            $period->year,
            $period->index,
            $period->start()->format('Y-m-d'),
            $period->end()->format('Y-m-d'),
            $period->deadline()->format('Y-m-d'),
            $figures->turnover->amount(),
            $figures->orderCount,
            UrssafContribution::on($figures->turnover)->amount(),
            match (true) {
                null !== $declaration => $declaration->turnover()->equals($figures->turnover) ? 'declared' : 'changed',
                $period->end()->format('Y-m-d') < $activityStart->setTimezone($period->end()->getTimezone())->format('Y-m-d') => 'inactive',
                !$period->isOverOn($today) => $period->covers($today) ? 'current' : 'upcoming',
                $period->isLateOn($today) => 'late',
                default => 'due',
            },
            $declaration?->turnover()->amount(),
            $declaration?->declaredAt()->format(\DateTimeInterface::ATOM),
        );
    }
}
