<?php

declare(strict_types=1);

namespace App\Domain\Notebook;

final readonly class RecognizedPage
{
    public const float GAP_FACTOR = 1.6;

    /**
     * @param list<RecognizedLine> $lines
     */
    public function __construct(
        public array $lines,
    ) {
    }

    public function text(): string
    {
        $gap = self::GAP_FACTOR * $this->usualSpacing();
        $text = [];
        $previousTop = null;
        foreach ($this->lines as $line) {
            if (null !== $previousTop && $line->top - $previousTop > $gap) {
                $text[] = '';
            }
            $text[] = $line->text;
            $previousTop = $line->top;
        }

        return implode("\n", $text);
    }

    private function usualSpacing(): float
    {
        $spacings = [];
        for ($index = 1, $count = \count($this->lines); $index < $count; ++$index) {
            $spacing = $this->lines[$index]->top - $this->lines[$index - 1]->top;
            if ($spacing > 0) {
                $spacings[] = $spacing;
            }
        }
        if ([] === $spacings) {
            return \INF;
        }
        sort($spacings);

        return $spacings[intdiv(\count($spacings) - 1, 2)];
    }
}
