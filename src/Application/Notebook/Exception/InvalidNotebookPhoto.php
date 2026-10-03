<?php

declare(strict_types=1);

namespace App\Application\Notebook\Exception;

use App\Domain\Shared\Exception\DomainException;

final class InvalidNotebookPhoto extends DomainException
{
    public function __construct(int $maxMegabytes)
    {
        parent::__construct('notebook.invalid_photo', ['megabytes' => $maxMegabytes]);
    }
}
