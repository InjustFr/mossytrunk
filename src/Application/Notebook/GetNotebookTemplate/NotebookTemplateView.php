<?php

declare(strict_types=1);

namespace App\Application\Notebook\GetNotebookTemplate;

use App\Domain\Notebook\Abbreviation;
use App\Domain\Notebook\NotebookTemplate;

final readonly class NotebookTemplateView
{
    /**
     * @param list<array{short: string, full: string}> $abbreviations
     */
    public function __construct(
        public string $separation,
        public array $abbreviations,
    ) {
    }

    public static function of(NotebookTemplate $template): self
    {
        return new self(
            $template->separation()->value,
            array_map(static fn (Abbreviation $abbreviation): array => ['short' => $abbreviation->short, 'full' => $abbreviation->full], $template->abbreviations()),
        );
    }
}
