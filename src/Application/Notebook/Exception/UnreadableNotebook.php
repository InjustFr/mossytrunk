<?php

declare(strict_types=1);

namespace App\Application\Notebook\Exception;

use App\Domain\Shared\Exception\DomainException;

final class UnreadableNotebook extends DomainException
{
    public function __construct(string $reason)
    {
        parent::__construct('notebook.unreadable', ['reason' => $reason]);
    }
}
