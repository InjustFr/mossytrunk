<?php

declare(strict_types=1);

namespace App\Domain\Design\Exception;

final class AlreadyDeclined extends InvalidDesign
{
    public function __construct(string $gabarit)
    {
        parent::__construct('design.already_adapted', ['template' => $gabarit]);
    }
}
