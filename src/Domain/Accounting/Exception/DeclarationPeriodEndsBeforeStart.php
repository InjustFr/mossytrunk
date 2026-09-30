<?php

declare(strict_types=1);

namespace App\Domain\Accounting\Exception;

final class DeclarationPeriodEndsBeforeStart extends InvalidDeclaration
{
    public function __construct()
    {
        parent::__construct('La fin de la période doit suivre son début.');
    }
}
