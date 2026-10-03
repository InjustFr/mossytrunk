<?php

declare(strict_types=1);

namespace App\Domain\Notebook\Exception;

final class DuplicateAbbreviation extends InvalidNotebook
{
    public function __construct(string $short)
    {
        parent::__construct('notebook.duplicate_abbreviation', ['short' => $short]);
    }
}
