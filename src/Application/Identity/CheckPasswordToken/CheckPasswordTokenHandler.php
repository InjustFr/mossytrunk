<?php

declare(strict_types=1);

namespace App\Application\Identity\CheckPasswordToken;

use App\Application\Identity\PasswordTokenIssuer;
use App\Domain\Identity\PasswordTokenPurpose;

final readonly class CheckPasswordTokenHandler
{
    public function __construct(private PasswordTokenIssuer $issuer)
    {
    }

    public function __invoke(string $token): PasswordTokenPurpose
    {
        return $this->issuer->resolve($token)[0]->purpose();
    }
}
