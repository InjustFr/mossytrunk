<?php

declare(strict_types=1);

namespace App\Domain\Design\Exception;

final class GabaritAlreadyExists extends InvalidDesign
{
    public function __construct(string $name)
    {
        parent::__construct('design.template_already_exists', ['name' => $name]);
    }
}
