<?php

declare(strict_types=1);

namespace App\Domain\Reference\Exception;

final class InvalidReferenceTokenSize extends InvalidReferenceTemplate
{
    public function __construct(string $token, int $min, int $max)
    {
        parent::__construct('reference.invalid_token_size', ['token' => '{'.$token.'}', 'min' => $min, 'max' => $max]);
    }
}
