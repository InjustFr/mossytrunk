<?php

declare(strict_types=1);

namespace App\Application\Integration;

interface AuthorizingConnector extends SalesConnector
{
    public function authorizationUrl(Credentials $credentials, string $redirectUri, string $state, string $codeChallenge): string;

    public function authorize(Credentials $credentials, string $code, string $codeVerifier, string $redirectUri): Authorization;

    public function refresh(Credentials $credentials): Tokens;
}
