<?php

declare(strict_types=1);

namespace App\Infrastructure\Notebook;

use App\Application\Notebook\HandwritingRecognizer;
use App\Application\Notebook\NotebookPage;
use App\Domain\Notebook\RecognizedLine;
use App\Domain\Notebook\RecognizedPage;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class FakeHandwritingRecognizer implements HandwritingRecognizer
{
    private const float LINE_HEIGHT = 50.0;

    private ?RecognizedPage $page = null;

    public function __construct(
        #[Autowire('%kernel.project_dir%/tests/Fixtures/notebook/page.txt')]
        private readonly string $fixture,
    ) {
    }

    public function willRecognize(RecognizedPage $page): void
    {
        $this->page = $page;
    }

    public function recognize(NotebookPage $page): RecognizedPage
    {
        if (null !== $this->page) {
            return $this->page;
        }

        $lines = [];
        $top = 0.0;
        foreach (explode("\n", trim((string) file_get_contents($this->fixture))) as $text) {
            $top += self::LINE_HEIGHT;
            if ('' !== trim($text)) {
                $lines[] = new RecognizedLine($text, $top);
            }
        }

        return new RecognizedPage($lines);
    }
}
