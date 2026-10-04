<?php

declare(strict_types=1);

namespace App\Application\Design\ListDesigns;

use App\Application\Design\DesignView;

final readonly class DesignBoard
{
    /**
     * @param list<array{id: string, name: string, description: ?string, current: bool, validated: bool, readyToValidate: bool, designs: list<DesignView>}> $collections
     * @param list<DesignView>                                                                                                                              $standalone
     */
    public function __construct(
        public array $collections,
        public array $standalone,
    ) {
    }
}
