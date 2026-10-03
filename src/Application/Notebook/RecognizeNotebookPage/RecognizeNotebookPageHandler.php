<?php

declare(strict_types=1);

namespace App\Application\Notebook\RecognizeNotebookPage;

use App\Application\Notebook\HandwritingRecognizer;
use App\Application\Notebook\NotebookPage;

final readonly class RecognizeNotebookPageHandler
{
    public const int MAX_PHOTO_MEGABYTES = 10;

    public function __construct(private HandwritingRecognizer $recognizer)
    {
    }

    public function __invoke(NotebookPage $page): string
    {
        return $this->recognizer->recognize($page)->text();
    }
}
