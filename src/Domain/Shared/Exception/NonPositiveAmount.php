<?php

declare(strict_types=1);

namespace App\Domain\Shared\Exception;

final class NonPositiveAmount extends InvalidMoney
{
    public function __construct(string $subject)
    {
        parent::__construct('shared.non_positive_amount', ['subject' => $subject]);
    }
}
