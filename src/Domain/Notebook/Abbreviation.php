<?php

declare(strict_types=1);

namespace App\Domain\Notebook;

use App\Domain\Notebook\Exception\EmptyAbbreviation;

final readonly class Abbreviation
{
    public string $short;
    public string $full;

    public function __construct(string $short, string $full)
    {
        $this->short = trim($short);
        $this->full = trim($full);
        if ('' === $this->short || '' === $this->full) {
            throw new EmptyAbbreviation();
        }
    }

    public function expand(string $text): string
    {
        return preg_replace('/(?<![\p{L}\p{N}])'.preg_quote($this->short, '/').'(?![\p{L}\p{N}])/iu', $this->full, $text) ?? $text;
    }

    public function key(): string
    {
        return mb_strtolower($this->short);
    }
}
