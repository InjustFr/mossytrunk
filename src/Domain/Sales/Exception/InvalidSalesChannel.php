<?php

declare(strict_types=1);

namespace App\Domain\Sales\Exception;

use App\Domain\Shared\Exception\DomainException;

abstract class InvalidSalesChannel extends DomainException
{
}
