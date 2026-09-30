<?php

declare(strict_types=1);

namespace App\Domain\Design\Exception;

final class EmptyDesignName extends InvalidDesign
{
    public function __construct(string $subject)
    {
        parent::__construct('design.empty_name', ['subject' => $subject]);
    }
}
