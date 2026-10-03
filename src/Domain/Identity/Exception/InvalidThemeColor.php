<?php

declare(strict_types=1);

namespace App\Domain\Identity\Exception;

final class InvalidThemeColor extends InvalidAccount
{
    public function __construct(string $color)
    {
        parent::__construct('identity.invalid_theme_color', ['color' => $color]);
    }
}
