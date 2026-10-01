<?php

declare(strict_types=1);

namespace App\Application\Reference;

use App\Domain\Reference\ReferenceFormatRepository;
use App\Domain\Reference\ReferenceKind;
use App\Domain\Reference\ReferenceSubject;

final class ReferenceGenerator
{
    /** @var array<string, array<string, true>> */
    private array $issued = [];

    public function __construct(
        private readonly ReferenceFormatRepository $formats,
        private readonly ReferenceBook $book,
    ) {
    }

    public function next(ReferenceKind $kind, ReferenceSubject $subject): string
    {
        $items = $this->book->of($kind);
        $reference = $this->formats->of($kind)->issue(
            $subject,
            fn (string $candidate): bool => isset($this->issued[$kind->value][$candidate]) || $items->holds($candidate),
        );
        $this->issued[$kind->value][$reference] = true;

        return $reference;
    }

    public function reset(): void
    {
        $this->issued = [];
    }
}
