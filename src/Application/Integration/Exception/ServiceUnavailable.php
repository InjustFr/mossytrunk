<?php

declare(strict_types=1);

namespace App\Application\Integration\Exception;

use App\Domain\Shared\Exception\DomainException;

abstract class ServiceUnavailable extends DomainException
{
}
