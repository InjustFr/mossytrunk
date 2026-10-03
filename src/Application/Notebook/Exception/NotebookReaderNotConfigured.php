<?php

declare(strict_types=1);

namespace App\Application\Notebook\Exception;

use App\Domain\Shared\Exception\DomainException;

final class NotebookReaderNotConfigured extends DomainException
{
    public function __construct()
    {
        parent::__construct('notebook.reader_not_configured');
    }
}
