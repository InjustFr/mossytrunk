<?php

declare(strict_types=1);

namespace App\Presentation\Api\Notebook;

use App\Application\Notebook\ConfigureNotebookTemplate\ConfigureNotebookTemplate;
use App\Domain\Notebook\Abbreviation;
use App\Domain\Notebook\SaleSeparation;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class NotebookTemplatePayload
{
    /**
     * @param list<AbbreviationPayload> $abbreviations
     */
    public function __construct(
        #[Assert\Choice(callback: [self::class, 'separations'], message: 'notebook.separation.invalid')]
        public string $separation = '',
        #[Assert\Valid]
        public array $abbreviations = [],
    ) {
    }

    /**
     * @return list<string>
     */
    public static function separations(): array
    {
        return array_map(static fn (SaleSeparation $separation): string => $separation->value, SaleSeparation::cases());
    }

    public function toCommand(): ConfigureNotebookTemplate
    {
        return new ConfigureNotebookTemplate(
            SaleSeparation::from($this->separation),
            array_map(static fn (AbbreviationPayload $abbreviation): Abbreviation => new Abbreviation($abbreviation->short, $abbreviation->full), $this->abbreviations),
        );
    }
}
