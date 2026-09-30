<?php

declare(strict_types=1);

namespace App\Domain\Shared\Exception;

final class NegativeAmount extends InvalidMoney
{
    public function __construct(string $subject)
    {
        parent::__construct('shared.negative_amount', ['subject' => $subject]);
    }
}
