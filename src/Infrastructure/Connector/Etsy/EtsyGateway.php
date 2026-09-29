<?php

declare(strict_types=1);

namespace App\Infrastructure\Connector\Etsy;

use App\Application\Integration\ExternalSale;
use App\Application\Integration\Tokens;

interface EtsyGateway
{
    public function authorizationUrl(EtsyApp $app, string $redirectUri, string $state, string $codeChallenge): string;

    public function exchangeCode(EtsyApp $app, string $code, string $codeVerifier, string $redirectUri): Tokens;

    public function refresh(EtsyApp $app, string $refreshToken): Tokens;

    public function shop(EtsyApp $app, string $accessToken): EtsyShop;

    /**
     * @return iterable<ExternalSale>
     */
    public function paidReceipts(EtsyApp $app, string $accessToken, string $shopId): iterable;
}
