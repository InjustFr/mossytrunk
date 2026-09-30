<?php

declare(strict_types=1);

namespace App\Domain\Design\Exception;

final class AdaptationsPending extends InvalidDesign
{
    public function __construct(string $declination, int $count)
    {
        parent::__construct('design.adjustments_pending', ['name' => $declination, 'count' => $count]);
    }
}
