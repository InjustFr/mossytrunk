<?php

declare(strict_types=1);

namespace App\Domain\Accounting\Exception;

final class UnknownDeclarationPeriod extends InvalidDeclaration
{
    public function __construct(string $key)
    {
        parent::__construct(\sprintf('Période de déclaration inconnue « %s ».', $key));
    }
}
