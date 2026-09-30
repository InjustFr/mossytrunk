<?php

declare(strict_types=1);

namespace App\Domain\Purchasing\Exception;

use App\Domain\Shared\Exception\DomainException;

abstract class InvalidPurchase extends DomainException
{
}
