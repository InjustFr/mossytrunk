<?php

declare(strict_types=1);

namespace App\Domain\Reference\Exception;

final class EmptyReferenceTemplate extends InvalidReferenceTemplate
{
    public function __construct()
    {
        parent::__construct('reference.empty_template');
    }
}
