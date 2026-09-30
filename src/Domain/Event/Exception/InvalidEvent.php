<?php

declare(strict_types=1);

namespace App\Domain\Event\Exception;

use App\Domain\Shared\Exception\DomainException;

abstract class InvalidEvent extends DomainException
{
}
