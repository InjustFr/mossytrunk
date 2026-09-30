<?php

declare(strict_types=1);

namespace App\Domain\Design\Exception;

final class AlreadyDeclined extends InvalidDesign
{
    public function __construct(string $gabarit)
    {
        parent::__construct(\sprintf('Ce design est déjà décliné en « %s ».', $gabarit));
    }
}
