<?php

declare(strict_types=1);

namespace App\Domain\Reference\Exception;

final class UnknownReferenceToken extends InvalidReferenceTemplate
{
    public function __construct(string $token)
    {
        parent::__construct('reference.unknown_token', ['token' => '{'.$token.'}']);
    }
}
