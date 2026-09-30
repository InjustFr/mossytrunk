<?php

declare(strict_types=1);

namespace App\Domain\Shared\Exception;

final class NonPositiveAmount extends InvalidMoney
{
    public function __construct(string $what)
    {
        parent::__construct(\sprintf('%s doit être supérieur à zéro.', $what));
    }
}
