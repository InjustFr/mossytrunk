<?php

declare(strict_types=1);

namespace App\Presentation\Api\Notebook;

use App\Application\Notebook\ScanNotebook\ScanNotebook;
use App\Application\Notebook\ScanNotebook\ScanNotebookHandler;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class NotebookPayload
{
    /**
     * @param list<string> $pages
     */
    public function __construct(
        #[Assert\Count(min: 1, max: ScanNotebookHandler::MAX_PAGES, minMessage: 'notebook.pages.count', maxMessage: 'notebook.pages.count')]
        #[Assert\All([new Assert\Type('string'), new Assert\Length(max: 20_000)])]
        public array $pages = [],
    ) {
    }

    public function toCommand(string $eventId): ScanNotebook
    {
        return new ScanNotebook($eventId, $this->pages);
    }
}
