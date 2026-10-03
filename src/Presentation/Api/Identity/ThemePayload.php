<?php

declare(strict_types=1);

namespace App\Presentation\Api\Identity;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class ThemePayload
{
    public function __construct(
        #[Assert\CssColor(formats: Assert\CssColor::HEX_LONG, message: 'theme.color.invalid')]
        public string $background = '',
        #[Assert\CssColor(formats: Assert\CssColor::HEX_LONG, message: 'theme.color.invalid')]
        public string $accent = '',
    ) {
    }
}
