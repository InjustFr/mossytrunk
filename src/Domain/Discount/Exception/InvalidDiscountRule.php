<?php

declare(strict_types=1);

namespace App\Domain\Discount\Exception;

use App\Domain\Shared\Exception\DomainException;

abstract class InvalidDiscountRule extends DomainException
{
}
