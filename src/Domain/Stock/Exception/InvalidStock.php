<?php

declare(strict_types=1);

namespace App\Domain\Stock\Exception;

use App\Domain\Shared\Exception\DomainException;

abstract class InvalidStock extends DomainException
{
}
