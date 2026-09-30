<?php

declare(strict_types=1);

namespace App\Application\Integration\Exception;

final class ServiceUnreachable extends ServiceUnavailable
{
    public function __construct(string $label, string $reason)
    {
        parent::__construct(\sprintf('Impossible de joindre %s : %s', $label, $reason));
    }
}
