<?php

declare(strict_types=1);

namespace App\Domain\Accounting\Exception;

final class DeclarationPeriodNotOver extends InvalidDeclaration
{
    public function __construct(string $key)
    {
        parent::__construct('accounting.period_not_over', ['period' => $key]);
    }
}
