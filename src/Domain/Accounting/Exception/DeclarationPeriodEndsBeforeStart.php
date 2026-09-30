<?php

declare(strict_types=1);

namespace App\Domain\Accounting\Exception;

final class DeclarationPeriodEndsBeforeStart extends InvalidDeclaration
{
    public function __construct()
    {
        parent::__construct('accounting.period_ends_before_start');
    }
}
