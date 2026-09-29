<?php

declare(strict_types=1);

namespace App\Application\Design\ListGabarits;

use App\Application\Design\GabaritView;
use App\Domain\Design\GabaritRepository;

final readonly class ListGabaritsHandler
{
    public function __construct(
        private GabaritRepository $gabarits,
    ) {
    }

    /**
     * @return list<GabaritView>
     */
    public function __invoke(): array
    {
        return array_map(GabaritView::of(...), $this->gabarits->all());
    }
}
