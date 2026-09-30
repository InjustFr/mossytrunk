<?php

declare(strict_types=1);

namespace App\Application\Design;

use App\Domain\Design\Gabarit;

final readonly class GabaritView
{
    /**
     * @param list<string> $variants
     * @param list<string> $adaptations
     */
    public function __construct(
        public string $id,
        public string $name,
        public string $typeId,
        public string $typeName,
        public int $sellingPrice,
        public array $variants,
        public array $adaptations,
    ) {
    }

    public static function of(Gabarit $gabarit): self
    {
        return new self(
            (string) $gabarit->id(),
            $gabarit->name(),
            (string) $gabarit->type()->id(),
            $gabarit->type()->name(),
            $gabarit->sellingPrice()->amount(),
            $gabarit->variants(),
            $gabarit->adaptations(),
        );
    }
}
