<?php

declare(strict_types=1);

namespace App\Domain\Design\Exception;

final class AdaptationsPending extends InvalidDesign
{
    public function __construct(string $declination, int $count)
    {
        parent::__construct(\sprintf('« %s » a encore %d adaptation%s à faire.', $declination, $count, $count > 1 ? 's' : ''));
    }
}
