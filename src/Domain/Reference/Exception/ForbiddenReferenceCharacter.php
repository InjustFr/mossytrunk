<?php

declare(strict_types=1);

namespace App\Domain\Reference\Exception;

final class ForbiddenReferenceCharacter extends InvalidReferenceTemplate
{
    public function __construct(string $character)
    {
        parent::__construct('reference.forbidden_character', ['character' => $character]);
    }
}
