<?php

declare(strict_types=1);

namespace App\Application\Reference\PreviewReferenceFormat;

use App\Application\Reference\ReferenceExample;
use App\Domain\Reference\ReferenceFormatRepository;
use App\Domain\Reference\ReferenceKind;
use App\Domain\Reference\ReferenceTemplate;

final readonly class PreviewReferenceFormatHandler
{
    public function __construct(
        private ReferenceFormatRepository $formats,
        private ReferenceExample $example,
    ) {
    }

    public function __invoke(ReferenceKind $kind, string $template): string
    {
        return $this->example->of(ReferenceTemplate::of($kind, $template), $this->formats->of($kind)->nextNumber());
    }
}
