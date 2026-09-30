<?php

declare(strict_types=1);

namespace App\Domain\Accounting\Exception;

use App\Domain\Shared\Exception\DomainException;

abstract class InvalidDeclaration extends DomainException
{
}
