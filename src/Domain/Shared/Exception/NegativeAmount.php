<?php

declare(strict_types=1);

namespace App\Domain\Shared\Exception;

final class NegativeAmount extends InvalidMoney
{
    public function __construct(string $what)
    {
        parent::__construct(\sprintf('%s ne peut pas être négatif.', $what));
    }
}
