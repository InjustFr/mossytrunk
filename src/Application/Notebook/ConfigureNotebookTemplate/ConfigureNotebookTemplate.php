<?php

declare(strict_types=1);

namespace App\Application\Notebook\ConfigureNotebookTemplate;

use App\Domain\Notebook\Abbreviation;
use App\Domain\Notebook\SaleSeparation;

final readonly class ConfigureNotebookTemplate
{
    /**
     * @param list<Abbreviation> $abbreviations
     */
    public function __construct(
        public SaleSeparation $separation,
        public array $abbreviations,
    ) {
    }
}
