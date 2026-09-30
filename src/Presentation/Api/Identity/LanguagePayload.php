<?php

declare(strict_types=1);

namespace App\Presentation\Api\Identity;

use App\Domain\Identity\Language;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class LanguagePayload
{
    public function __construct(
        #[Assert\Choice(callback: [Language::class, 'codes'], message: 'language.invalid')]
        public string $language = '',
    ) {
    }
}
