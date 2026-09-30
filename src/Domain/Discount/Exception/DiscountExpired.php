<?php

declare(strict_types=1);

namespace App\Domain\Discount\Exception;

final class DiscountExpired extends InvalidDiscountRule
{
    public function __construct(string $name)
    {
        parent::__construct(\sprintf('« %s » est expirée : modifiez ses dates pour la relancer.', $name));
    }
}
