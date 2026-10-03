<?php

declare(strict_types=1);

namespace App\Application\Notebook\Exception;

use App\Domain\Shared\Exception\DomainException;

final class InvalidNotebookPages extends DomainException
{
    public function __construct(int $maxPages)
    {
        parent::__construct('notebook.invalid_pages', ['max' => $maxPages]);
    }
}
