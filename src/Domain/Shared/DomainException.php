<?php

declare(strict_types=1);

namespace App\Domain\Shared;

/**
 * Base class for business rule violations. Messages are user-facing (French).
 */
abstract class DomainException extends \DomainException
{
}
