<?php

declare(strict_types=1);

namespace App\Application\Reference;

use App\Domain\Reference\ReferenceSubject;
use App\Domain\Reference\ReferenceTemplate;
use Psr\Clock\ClockInterface;

final readonly class ReferenceExample
{
    private const string TYPE_CODE = 'PRI';
    private const string NAME_CODE = 'FOR';

    public function __construct(private ClockInterface $clock)
    {
    }

    public function of(ReferenceTemplate $template, int $number): string
    {
        return $template->render(ReferenceSubject::named($this->clock->now(), self::TYPE_CODE, self::NAME_CODE), $number);
    }
}
