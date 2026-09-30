<?php

declare(strict_types=1);

namespace App\Application\Integration\Exception;

final class ServiceNotAuthorizing extends ServiceUnavailable
{
    public function __construct(string $label)
    {
        parent::__construct(\sprintf('%s ne se connecte pas par autorisation.', $label));
    }
}
