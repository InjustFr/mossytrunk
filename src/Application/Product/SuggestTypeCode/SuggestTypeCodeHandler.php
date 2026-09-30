<?php

declare(strict_types=1);

namespace App\Application\Product\SuggestTypeCode;

use App\Domain\Product\TypeCodeGenerator;

final readonly class SuggestTypeCodeHandler
{
    public function __construct(private TypeCodeGenerator $codes)
    {
    }

    public function __invoke(string $name): string
    {
        return $this->codes->generate($name);
    }
}
