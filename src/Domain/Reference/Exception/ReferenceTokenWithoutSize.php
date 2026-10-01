<?php

declare(strict_types=1);

namespace App\Domain\Reference\Exception;

final class ReferenceTokenWithoutSize extends InvalidReferenceTemplate
{
    public function __construct(string $token)
    {
        parent::__construct('reference.token_without_size', ['token' => '{'.$token.'}']);
    }
}
