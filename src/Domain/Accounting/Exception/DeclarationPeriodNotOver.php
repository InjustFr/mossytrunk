<?php

declare(strict_types=1);

namespace App\Domain\Accounting\Exception;

final class DeclarationPeriodNotOver extends InvalidDeclaration
{
    public function __construct(string $key)
    {
        parent::__construct(\sprintf('La période %s n\'est pas terminée : elle ne peut pas encore être déclarée.', $key));
    }
}
