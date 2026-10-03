<?php

declare(strict_types=1);

namespace App\Application\Notebook;

use App\Domain\Notebook\RecognizedPage;

interface HandwritingRecognizer
{
    public function recognize(NotebookPage $page): RecognizedPage;
}
