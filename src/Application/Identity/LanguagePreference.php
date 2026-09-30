<?php

declare(strict_types=1);

namespace App\Application\Identity;

use App\Domain\Identity\Language;

interface LanguagePreference
{
    public function ofSignedInUser(): ?Language;
}
