<?php

declare(strict_types=1);

namespace App\Domain\Discount\Exception;

final class DiscountNotRunning extends InvalidDiscountRule
{
    public function __construct(string $name)
    {
        parent::__construct(\sprintf('« %s » n\'est pas en cours.', $name));
    }
}
